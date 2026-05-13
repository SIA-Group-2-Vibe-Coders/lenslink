// frontend/assets/js/chat.js
// LensLink Chat Module — powered by Pusher

(function () {
    'use strict';

    // ─── Config ──────────────────────────────────────────────────────────────
    // Replace with your actual Pusher App Key (from dashboard.pusher.com)
    const PUSHER_APP_KEY    = 'd6fc05d33b9792276bd8';
    const PUSHER_APP_CLUSTER = 'ap1';

    // ─── State ───────────────────────────────────────────────────────────────
    let pusherInstance   = null;
    let activeChannel    = null;
    let currentGalleryId = null;
    let currentUser      = null;

    // ─── Init ─────────────────────────────────────────────────────────────────
    /**
     * Call this from the page after auth is confirmed.
     * @param {object} user   - the authenticated user object { id, name, role_id }
     * @param {number} galleryId - the gallery_id to open a chat for
     */
    window.initChat = function (user, galleryId) {
        currentUser      = user;
        currentGalleryId = galleryId;

        injectChatWidget();
        loadHistory(galleryId);
        connectPusher(galleryId);
    };

    // ─── Pusher Connection ────────────────────────────────────────────────────
    function connectPusher(galleryId) {
        pusherInstance = new Pusher(PUSHER_APP_KEY, {
            cluster: PUSHER_APP_CLUSTER,
            forceTLS: true,
            authEndpoint: (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost')
                ? 'http://127.0.0.1:8000/broadcasting/auth'
                : 'https://lenslink-api-3w31.onrender.com/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: 'Bearer ' + localStorage.getItem('auth_token'),
                    Accept: 'application/json',
                },
            },
        });

        activeChannel = pusherInstance.subscribe('private-chat.' + galleryId);

        activeChannel.bind('message.sent', function (data) {
            appendMessage(data, false);
        });

        activeChannel.bind('pusher:subscription_error', function () {
            console.error('Chat auth failed. Check Pusher credentials and channel authorization.');
        });
    }

    // ─── Load History ─────────────────────────────────────────────────────────
    async function loadHistory(galleryId) {
        setChatStatus('Loading messages…');
        try {
            const res = await apiCall('messages?gallery_id=' + galleryId);
            const list = document.getElementById('ll-chat-messages');
            list.innerHTML = '';

            if (res.status === 'success' && res.data.length > 0) {
                res.data.forEach(msg => appendMessage(msg, false));
            } else {
                list.innerHTML = '<p class="ll-chat-empty">No messages yet. Say hello! 👋</p>';
            }
            scrollToBottom();
        } catch (e) {
            setChatStatus('Failed to load messages.');
        }
    }

    // ─── Send Message ─────────────────────────────────────────────────────────
    async function sendMessage(body) {
        if (!body.trim()) return;

        // Optimistic: show immediately
        appendMessage({
            id: 'temp-' + Date.now(),
            body,
            sender: currentUser,
            created_at: new Date().toISOString(),
        }, true);

        try {
            await apiCall('messages', 'POST', {
                gallery_id: currentGalleryId,
                body,
            });
        } catch (e) {
            console.error('Failed to send message', e);
        }
    }

    // ─── DOM Helpers ──────────────────────────────────────────────────────────
    function appendMessage(msg, isSelf) {
        const list = document.getElementById('ll-chat-messages');
        const empty = list.querySelector('.ll-chat-empty');
        if (empty) empty.remove();

        const isOwn = isSelf || (currentUser && msg.sender && msg.sender.id === currentUser.id);
        const time  = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const name  = msg.sender ? msg.sender.name : 'You';

        const bubble = document.createElement('div');
        bubble.className = 'll-chat-bubble' + (isOwn ? ' ll-chat-bubble--own' : '');
        bubble.innerHTML = `
            <span class="ll-chat-sender">${isOwn ? 'You' : name}</span>
            <p class="ll-chat-body">${escapeHtml(msg.body)}</p>
            <span class="ll-chat-time">${time}</span>
        `;

        list.appendChild(bubble);
        scrollToBottom();
    }

    function scrollToBottom() {
        const list = document.getElementById('ll-chat-messages');
        if (list) list.scrollTop = list.scrollHeight;
    }

    function setChatStatus(text) {
        const el = document.getElementById('ll-chat-messages');
        if (el) el.innerHTML = `<p class="ll-chat-empty">${text}</p>`;
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    // ─── Widget Injection ─────────────────────────────────────────────────────
    function injectChatWidget() {
        // Don't inject twice
        if (document.getElementById('ll-chat-widget')) return;

        // Inject styles
        const style = document.createElement('style');
        style.textContent = `
            #ll-chat-toggle {
                position: fixed; bottom: 28px; right: 28px; z-index: 9999;
                width: 56px; height: 56px; border-radius: 50%;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                border: none; cursor: pointer; box-shadow: 0 4px 24px rgba(245,158,11,0.4);
                display: flex; align-items: center; justify-content: center;
                transition: transform .2s, box-shadow .2s;
            }
            #ll-chat-toggle:hover { transform: scale(1.1); box-shadow: 0 8px 32px rgba(245,158,11,0.55); }
            #ll-chat-toggle svg { width: 26px; height: 26px; color: #1c1917; }
            #ll-chat-badge {
                position: absolute; top: -4px; right: -4px;
                background: #ef4444; color: white; font-size: 10px; font-weight: 700;
                border-radius: 999px; min-width: 18px; height: 18px;
                display: none; align-items: center; justify-content: center;
                padding: 0 4px;
            }

            #ll-chat-widget {
                position: fixed; bottom: 96px; right: 28px; z-index: 9998;
                width: 360px; max-height: 520px;
                background: rgba(24,24,27,0.97);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(245,158,11,0.2);
                border-radius: 20px;
                display: none; flex-direction: column;
                box-shadow: 0 20px 60px rgba(0,0,0,0.6), 0 0 0 1px rgba(245,158,11,0.1);
                overflow: hidden; font-family: 'Outfit', sans-serif;
                transform: translateY(12px) scale(0.97); opacity: 0;
                transition: transform .25s cubic-bezier(.4,0,.2,1), opacity .25s;
            }
            #ll-chat-widget.ll-chat-open {
                display: flex; opacity: 1; transform: translateY(0) scale(1);
            }

            #ll-chat-header {
                padding: 14px 18px; background: rgba(245,158,11,0.08);
                border-bottom: 1px solid rgba(245,158,11,0.15);
                display: flex; align-items: center; gap: 10px;
            }
            .ll-chat-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; flex-shrink: 0; box-shadow: 0 0 6px #22c55e; }
            #ll-chat-header h4 { margin: 0; font-size: 14px; font-weight: 600; color: #fff; flex: 1; }
            #ll-chat-header span { font-size: 11px; color: #a1a1aa; }

            #ll-chat-messages {
                flex: 1; overflow-y: auto; padding: 16px; display: flex;
                flex-direction: column; gap: 10px;
                scrollbar-width: thin; scrollbar-color: #3f3f46 transparent;
            }
            .ll-chat-empty { text-align: center; color: #71717a; font-size: 13px; margin: auto; }

            .ll-chat-bubble {
                max-width: 80%; display: flex; flex-direction: column; gap: 2px;
                align-self: flex-start;
            }
            .ll-chat-bubble--own { align-self: flex-end; align-items: flex-end; }
            .ll-chat-sender { font-size: 10px; color: #a1a1aa; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
            .ll-chat-body {
                margin: 0; padding: 9px 13px; border-radius: 14px;
                font-size: 13.5px; line-height: 1.5; word-break: break-word;
                background: #27272a; color: #e4e4e7;
                border-bottom-left-radius: 4px;
            }
            .ll-chat-bubble--own .ll-chat-body {
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: #1c1917; border-bottom-left-radius: 14px; border-bottom-right-radius: 4px;
            }
            .ll-chat-time { font-size: 10px; color: #52525b; }

            #ll-chat-form {
                padding: 12px 14px; border-top: 1px solid rgba(245,158,11,0.1);
                display: flex; gap: 8px; align-items: center;
            }
            #ll-chat-input {
                flex: 1; padding: 9px 14px; border-radius: 999px;
                background: #27272a; border: 1px solid #3f3f46; color: #e4e4e7;
                font-size: 13px; font-family: 'Outfit', sans-serif; outline: none;
                transition: border-color .2s;
            }
            #ll-chat-input:focus { border-color: #f59e0b; }
            #ll-chat-send {
                width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
                transition: transform .15s, box-shadow .15s;
            }
            #ll-chat-send:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
            #ll-chat-send svg { width: 16px; height: 16px; color: #1c1917; }
        `;
        document.head.appendChild(style);

        // Toggle button
        const toggle = document.createElement('button');
        toggle.id = 'll-chat-toggle';
        toggle.title = 'Chat';
        toggle.innerHTML = `
            <div id="ll-chat-badge"></div>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4-.847L3 20l1.075-3.224A7.963 7.963 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        `;
        document.body.appendChild(toggle);

        // Chat widget
        const widget = document.createElement('div');
        widget.id = 'll-chat-widget';
        widget.setAttribute('aria-label', 'Chat window');
        widget.innerHTML = `
            <div id="ll-chat-header">
                <div class="ll-chat-dot"></div>
                <h4>Gallery Chat</h4>
                <span id="ll-chat-gallery-label">Gallery #${currentGalleryId}</span>
            </div>
            <div id="ll-chat-messages">
                <p class="ll-chat-empty">Loading…</p>
            </div>
            <form id="ll-chat-form" autocomplete="off">
                <input id="ll-chat-input" type="text" placeholder="Type a message…" maxlength="2000" />
                <button id="ll-chat-send" type="submit" title="Send">
                    <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/>
                    </svg>
                </button>
            </form>
        `;
        document.body.appendChild(widget);

        // Events
        let isOpen = false;
        toggle.addEventListener('click', () => {
            isOpen = !isOpen;
            widget.classList.toggle('ll-chat-open', isOpen);
            document.getElementById('ll-chat-badge').style.display = 'none';
            if (isOpen) {
                scrollToBottom();
                document.getElementById('ll-chat-input').focus();
            }
        });

        document.getElementById('ll-chat-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('ll-chat-input');
            const body  = input.value.trim();
            if (!body) return;
            input.value = '';
            await sendMessage(body);
        });

        // Show badge on incoming message when widget is closed
        if (pusherInstance === null) {
            // Pusher not connected yet — badge will be wired in connectPusher
        }
    }
})();
