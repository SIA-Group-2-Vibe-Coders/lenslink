// frontend/assets/js/chat.js
// LensLink Chat Module — powered by Pusher

(function () {
    'use strict';

    // ─── Config ──────────────────────────────────────────────────────────────
    const PUSHER_APP_KEY    = 'd6fc05d33b9792276bd8';
    const PUSHER_APP_CLUSTER = 'ap1';

    // ─── State ───────────────────────────────────────────────────────────────
    let pusherInstance   = null;
    let activeChannel    = null;
    let currentGalleryId = null;
    let currentReceiverId = null;
    let currentUser      = null;
    let isInbox          = true; // New: default to inbox view

    // ─── Init ─────────────────────────────────────────────────────────────────
    /**
     * Initial setup for the chat system. Call once after user auth.
     * @param {object} user - the authenticated user object
     */
    window.initChat = function (user) {
        if (!user) return;
        currentUser = user;
        injectChatWidget();
    };

    /**
     * Opens the chat for a specific gallery. Switches context if already open.
     * @param {number} galleryId 
     */
    window.openChat = function (galleryId) {
        if (!galleryId) return;
        isInbox = false;
        
        // Show UI elements for active chat
        document.getElementById('ll-chat-back').style.display = 'flex';
        document.getElementById('ll-chat-form').style.display = 'flex';

        // If it's a different gallery or we were in direct chat, switch context
        if (currentGalleryId !== galleryId || currentReceiverId !== null) {
            currentGalleryId = galleryId;
            currentReceiverId = null;
            
            updateHeader('Gallery Chat', 'Gallery #' + galleryId, `https://ui-avatars.com/api/?background=f59e0b&color=18181b&name=G${galleryId}`);

            // Clear old messages
            const list = document.getElementById('ll-chat-messages');
            if (list) list.innerHTML = '<p class="ll-chat-empty">Loading history...</p>';

            // Handle Pusher context switch
            connectPusher();
            
            // Load new history
            loadHistory();
        }

        showWidget();
    };

    /**
     * Opens the chat with a specific user.
     * @param {number} receiverId 
     * @param {string} receiverName
     */
    window.openDirectChat = function (receiverId, receiverName) {
        if (!receiverId) return;
        isInbox = false;

        // Show UI elements for active chat
        document.getElementById('ll-chat-back').style.display = 'flex';
        document.getElementById('ll-chat-form').style.display = 'flex';

        // Switch context if different receiver or we were in gallery chat
        if (currentReceiverId !== receiverId || currentGalleryId !== null) {
            currentReceiverId = receiverId;
            currentGalleryId = null;

            updateHeader(receiverName || 'Direct Message', 'Direct Contact', `https://ui-avatars.com/api/?background=f59e0b&color=18181b&name=${encodeURIComponent(receiverName || 'U')}`);

            // Clear old messages
            const list = document.getElementById('ll-chat-messages');
            if (list) list.innerHTML = '<p class="ll-chat-empty">Loading conversation...</p>';

            // Handle Pusher context switch
            connectPusher();

            // Load new history
            loadHistory();
        }

        showWidget();
    };

    function showWidget() {
        const widget = document.getElementById('ll-chat-widget');
        if (widget) {
            widget.classList.add('ll-chat-open');
            document.getElementById('ll-chat-badge').style.display = 'none';
            if (!isInbox) {
                scrollToBottom();
                document.getElementById('ll-chat-input').focus();
            }
        }
    }

    // ─── Inbox Functions ───────────────────────────────────────────────────────
    window.openInbox = async function () {
        isInbox = true;
        currentGalleryId = null;
        currentReceiverId = null;

        // Reset UI to Inbox state
        document.getElementById('ll-chat-back').style.display = 'none';
        document.getElementById('ll-chat-form').style.display = 'none';
        updateHeader('LensLink Chat', 'Recent Conversations', 'https://ui-avatars.com/api/?background=27272a&color=fff&name=LL');

        const list = document.getElementById('ll-chat-messages');
        if (list) list.innerHTML = '<p class="ll-chat-empty">Loading conversations...</p>';

        try {
            const res = await apiCall('conversations');
            if (res.status === 'success') {
                renderInbox(res.data);
            } else {
                setChatStatus('Failed to load conversations.');
            }
        } catch (e) {
            setChatStatus('Error loading Inbox.');
        }
    };

    function renderInbox(contacts) {
        const list = document.getElementById('ll-chat-messages');
        if (!list) return;

        list.innerHTML = '';

        if (!contacts || contacts.length === 0) {
            list.innerHTML = '<p class="ll-chat-empty">Your inbox is empty.<br>Start chatting from a gallery or profile! ✉️</p>';
            return;
        }

        contacts.forEach(contact => {
            const item = document.createElement('div');
            item.className = 'll-chat-contact-item';
            
            const avatarUrl = contact.avatar 
                ? getStorageUrl(contact.avatar) 
                : `https://ui-avatars.com/api/?background=f59e0b&color=18181b&name=${encodeURIComponent(contact.name)}`;

            item.innerHTML = `
                <img src="${avatarUrl}" class="ll-chat-contact-avatar">
                <div class="ll-chat-contact-info">
                    <div class="ll-chat-contact-top">
                        <span class="ll-chat-contact-name">${contact.name}</span>
                        ${contact.unread_count > 0 ? `<span class="ll-chat-unread-badge">${contact.unread_count}</span>` : ''}
                    </div>
                    <span class="ll-chat-contact-last">${contact.last_message || 'No messages yet'}</span>
                </div>
            `;

            item.onclick = () => window.openDirectChat(contact.id, contact.name);
            list.appendChild(item);
        });
    }

    function updateHeader(title, label, avatar) {
        const headerTitle = document.getElementById('ll-chat-header-title');
        const headerLabel = document.getElementById('ll-chat-gallery-label');
        const headerAvatar = document.getElementById('ll-chat-header-avatar');

        if (headerTitle) headerTitle.textContent = title;
        if (headerLabel) headerLabel.textContent = label;
        if (headerAvatar) headerAvatar.src = avatar;
    }

    // ─── Pusher Connection ────────────────────────────────────────────────────
    function connectPusher() {
        // Initialize Pusher only once
        if (!pusherInstance) {
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
        }

        // Determine channel name
        let channelName = '';
        if (currentGalleryId) {
            channelName = 'private-chat.' + currentGalleryId;
        } else if (currentReceiverId) {
            // Deterministic ID-based channel for direct messages
            const ids = [currentUser.id, currentReceiverId].sort((a, b) => a - b);
            channelName = `private-chat.direct.${ids[0]}-${ids[1]}`;
        }

        if (!channelName) return;

        // Unsubscribe from previous channel if exists
        if (activeChannel) {
            pusherInstance.unsubscribe(activeChannel.name);
        }

        // Subscribe to new channel
        activeChannel = pusherInstance.subscribe(channelName);

        activeChannel.bind('message.sent', function (data) {
            // Validate context before appending
            const isMatch = currentGalleryId 
                ? (data.gallery_id == currentGalleryId)
                : (data.receiver_id == currentUser.id && data.sender.id == currentReceiverId) || 
                  (data.sender.id == currentUser.id && data.receiver_id == currentReceiverId);

            if (isMatch) {
                appendMessage(data, false);
                
                // Show badge if widget is closed
                const widget = document.getElementById('ll-chat-widget');
                if (widget && !widget.classList.contains('ll-chat-open')) {
                    const badge = document.getElementById('ll-chat-badge');
                    badge.style.display = 'flex';
                }
            }
        });

        activeChannel.bind('pusher:subscription_error', function (status) {
            console.error('Chat auth failed:', status);
            setChatStatus('Unauthorized to chat in this channel.');
        });
    }

    // ─── Load History ─────────────────────────────────────────────────────────
    async function loadHistory() {
        try {
            const query = currentGalleryId 
                ? 'gallery_id=' + currentGalleryId 
                : 'receiver_id=' + currentReceiverId;

            const res = await apiCall('messages?' + query);
            const list = document.getElementById('ll-chat-messages');
            if (!list) return;

            // Context check
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

        const payload = currentGalleryId 
            ? { gallery_id: currentGalleryId, body }
            : { receiver_id: currentReceiverId, body };

        // Optimistic: show immediately
        appendMessage({
            id: 'temp-' + Date.now(),
            body,
            sender: currentUser,
            created_at: new Date().toISOString(),
        }, true);

        try {
            await apiCall('messages', 'POST', payload);
        } catch (e) {
            console.error('Failed to send message', e);
        }
    }

    // ─── DOM Helpers ──────────────────────────────────────────────────────────
    function appendMessage(msg, isSelf) {
        const list = document.getElementById('ll-chat-messages');
        if (!list) return;

        const empty = list.querySelector('.ll-chat-empty');
        if (empty) empty.remove();

        const isOwn = isSelf || (currentUser && msg.sender && msg.sender.id === currentUser.id);
        const time  = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const name  = msg.sender ? msg.sender.name : 'User';

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
        if (document.getElementById('ll-chat-widget')) return;

        const style = document.createElement('style');
        style.textContent = `
            #ll-chat-toggle {
                position: fixed; bottom: 32px; right: 32px; z-index: 9999;
                width: 64px; height: 64px; border-radius: 50%;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                border: none; cursor: pointer; box-shadow: 0 8px 32px rgba(245,158,11,0.4);
                display: flex; align-items: center; justify-content: center;
                transition: all .3s cubic-bezier(.4,0,.2,1);
            }
            #ll-chat-toggle:hover { 
                transform: scale(1.1) rotate(5deg); 
                box-shadow: 0 12px 48px rgba(245,158,11,0.5); 
            }
            #ll-chat-toggle svg { width: 30px; height: 30px; color: #1c1917; }
            #ll-chat-badge {
                position: absolute; top: -2px; right: -2px;
                background: #ef4444; color: white; font-size: 11px; font-weight: 800;
                border-radius: 999px; min-width: 20px; height: 20px;
                display: none; align-items: center; justify-content: center;
                padding: 0 5px; border: 3px solid #0f0f11;
                animation: bounce 2s infinite;
            }
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-3px); }
            }

            #ll-chat-widget {
                position: fixed; bottom: 110px; right: 32px; z-index: 9998;
                width: 380px; height: 600px; max-height: calc(100vh - 160px);
                background: rgba(15,15,17,0.85);
                backdrop-filter: blur(24px) saturate(180%);
                -webkit-backdrop-filter: blur(24px) saturate(180%);
                border: 1px solid rgba(245,158,11,0.2);
                border-radius: 24px;
                display: none; flex-direction: column;
                box-shadow: 0 32px 128px rgba(0,0,0,0.8), 0 0 0 1px rgba(255,255,255,0.05);
                overflow: hidden; font-family: 'Outfit', sans-serif;
                transform: translateY(20px) scale(0.95); opacity: 0;
                transition: all .3s cubic-bezier(.34,1.56,.64,1);
                pointer-events: none;
            }
            #ll-chat-widget.ll-chat-open {
                display: flex; opacity: 1; transform: translateY(0) scale(1);
                pointer-events: auto;
            }

            #ll-chat-header {
                padding: 16px 20px; 
                background: linear-gradient(to bottom, rgba(245,158,11,0.1), transparent);
                border-bottom: 1px solid rgba(245,158,11,0.1);
                display: flex; align-items: center; gap: 12px;
            }
            #ll-chat-back {
                width: 32px; height: 32px; border-radius: 10px;
                display: none; align-items: center; justify-content: center;
                color: #a1a1aa; cursor: pointer; transition: all .2s;
                margin-left: -8px;
            }
            #ll-chat-back:hover { background: rgba(255,255,255,0.05); color: #fff; }
            #ll-chat-back svg { width: 18px; height: 18px; }

            .ll-chat-avatar-ring {
                width: 38px; height: 38px; border-radius: 12px;
                background: linear-gradient(45deg, #f59e0b, #d97706);
                padding: 2px; flex-shrink: 0;
            }
            .ll-chat-avatar {
                width: 100%; height: 100%; border-radius: 10px;
                object-fit: cover; background: #27272a;
            }
            #ll-chat-header-info { flex: 1; min-width: 0; }
            #ll-chat-header h4 { 
                margin: 0; font-size: 15px; font-weight: 700; color: #fff; 
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            #ll-chat-gallery-label { font-size: 11px; color: #a1a1aa; font-weight: 500; display: block; margin-top: 1px; }

            /* Inbox Styles */
            .ll-chat-contact-item {
                display: flex; gap: 14px; padding: 12px 16px; border-radius: 16px;
                cursor: pointer; transition: all .2s; margin-bottom: 4px;
            }
            .ll-chat-contact-item:hover { background: rgba(245,158,11,0.05); }
            .ll-chat-contact-avatar { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; border: 1px solid rgba(255,255,255,0.05); }
            .ll-chat-contact-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
            .ll-chat-contact-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px; }
            .ll-chat-contact-name { font-weight: 600; color: #fff; font-size: 14px; }
            .ll-chat-contact-last { font-size: 12px; color: #71717a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ll-chat-unread-badge { background: #f59e0b; color: #1c1917; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 10px; }

            #ll-chat-messages {
                flex: 1; overflow-y: auto; padding: 16px; display: flex;
                flex-direction: column; gap: 12px;
                scrollbar-width: none;
            }
            #ll-chat-messages::-webkit-scrollbar { display: none; }
            
            .ll-chat-empty { 
                text-align: center; color: #71717a; font-size: 14px; margin: auto;
                max-width: 200px; line-height: 1.6;
            }

            .ll-chat-bubble {
                max-width: 85%; display: flex; flex-direction: column; gap: 4px;
                align-self: flex-start;
                animation: slideInLeft .3s ease-out forwards;
            }
            .ll-chat-bubble--own { 
                align-self: flex-end; align-items: flex-end; 
                animation: slideInRight .3s ease-out forwards;
            }
            
            @keyframes slideInLeft {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes slideInRight {
                from { opacity: 0; transform: translateX(10px); }
                to { opacity: 1; transform: translateX(0); }
            }

            .ll-chat-sender { font-size: 10px; color: #71717a; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin: 0 4px; }
            .ll-chat-body {
                margin: 0; padding: 12px 16px; border-radius: 18px;
                font-size: 14px; line-height: 1.5; word-break: break-word;
                background: #27272a; color: #f4f4f5;
                border-bottom-left-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            .ll-chat-bubble--own .ll-chat-body {
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: #1c1917; border-bottom-left-radius: 18px; border-bottom-right-radius: 4px;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(245,158,11,0.2);
            }
            .ll-chat-time { font-size: 10px; color: #52525b; margin: 2px 6px; }

            #ll-chat-form {
                padding: 20px; border-top: 1px solid rgba(255,255,255,0.05);
                display: flex; gap: 12px; align-items: center; background: rgba(15,15,17,0.5);
            }
            #ll-chat-input-wrapper {
                flex: 1; position: relative;
            }
            #ll-chat-input {
                width: 100%; padding: 12px 18px; border-radius: 16px;
                background: #27272a; border: 1px solid transparent; color: #fff;
                font-size: 14px; font-family: 'Outfit', sans-serif; outline: none;
                transition: all .2s;
            }
            #ll-chat-input:focus { 
                background: #323235;
                border-color: rgba(245,158,11,0.5);
                box-shadow: 0 0 0 4px rgba(245,158,11,0.1);
            }
            #ll-chat-send {
                width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
                transition: all .2s;
                box-shadow: 0 4px 12px rgba(245,158,11,0.3);
            }
            #ll-chat-send:hover { 
                transform: translateY(-2px) scale(1.05); 
                box-shadow: 0 6px 20px rgba(245,158,11,0.4); 
            }
            #ll-chat-send:active { transform: translateY(0) scale(0.95); }
            #ll-chat-send svg { width: 20px; height: 20px; color: #1c1917; }
        `;
        document.head.appendChild(style);

        const toggle = document.createElement('button');
        toggle.id = 'll-chat-toggle';
        toggle.innerHTML = `<div id="ll-chat-badge">!</div><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4-.847L3 20l1.075-3.224A7.963 7.963 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>`;
        document.body.appendChild(toggle);

        const widget = document.createElement('div');
        widget.id = 'll-chat-widget';
        widget.innerHTML = `
            <div id="ll-chat-header">
                <div id="ll-chat-back" onclick="window.openInbox()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </div>
                <div class="ll-chat-avatar-ring">
                    <img id="ll-chat-header-avatar" class="ll-chat-avatar" src="https://ui-avatars.com/api/?background=27272a&color=fff&name=LL">
                </div>
                <div id="ll-chat-header-info">
                    <h4 id="ll-chat-header-title">LensLink Chat</h4>
                    <span id="ll-chat-gallery-label">Select a contact</span>
                </div>
            </div>
            <div id="ll-chat-messages">
                <p class="ll-chat-empty">Loading messages...</p>
            </div>
            <form id="ll-chat-form" autocomplete="off" style="display:none">
                <input id="ll-chat-input" type="text" placeholder="Type a message…" maxlength="2000" />
                <button id="ll-chat-send" type="submit">
                    <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
                </button>
            </form>
        `;
        document.body.appendChild(widget);

        toggle.addEventListener('click', () => {
            const isNowOpen = !widget.classList.contains('ll-chat-open');
            widget.classList.toggle('ll-chat-open', isNowOpen);
            document.getElementById('ll-chat-badge').style.display = 'none';
            if (isNowOpen) {
                if (isInbox) {
                    window.openInbox();
                } else {
                    scrollToBottom();
                    document.getElementById('ll-chat-input').focus();
                }
            }
        });

        document.getElementById('ll-chat-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('ll-chat-input');
            const body  = input.value.trim();
            if (!body || (!currentGalleryId && !currentReceiverId)) return;
            input.value = '';
            await sendMessage(body);
        });
    }
})();
