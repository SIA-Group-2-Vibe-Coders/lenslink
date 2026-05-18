// frontend/assets/js/feed.js

let currentUser = null;
let currentActivePostId = null;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', async () => {
    // Authenticate user
    currentUser = await checkAuth();
    if (currentUser) {
        document.getElementById('userNameDisplay').textContent = `Hi, ${currentUser.name}`;
        loadFeed();
    }
});

/**
 * Redirect user to their respective dashboard based on role
 */
function goToPortal() {
    if (!currentUser) return;
    const roleId = parseInt(currentUser.role_id);
    if (roleId === 1) {
        window.location.href = 'admin.html';
    } else {
        window.location.href = 'dashboard-photographer.html';
    }
}

/**
 * Show a premium toast notification
 */
function showToast(message, isSuccess = true) {
    const toast = document.getElementById('toastNotification');
    const msgSpan = document.getElementById('toastMsg');
    const iconSpan = document.getElementById('toastIcon');

    msgSpan.textContent = message;
    if (isSuccess) {
        toast.className = "fixed bottom-6 right-6 z-50 transform translate-y-0 opacity-100 transition-all duration-300 flex items-center gap-3 px-5 py-3.5 rounded-xl border border-emerald-500/30 bg-zinc-900/90 text-sm font-semibold max-w-sm shadow-2xl backdrop-blur-md";
        iconSpan.innerHTML = '<svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    } else {
        toast.className = "fixed bottom-6 right-6 z-50 transform translate-y-0 opacity-100 transition-all duration-300 flex items-center gap-3 px-5 py-3.5 rounded-xl border border-rose-500/30 bg-zinc-900/90 text-sm font-semibold max-w-sm shadow-2xl backdrop-blur-md";
        iconSpan.innerHTML = '<svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
    }

    setTimeout(() => {
        toast.className = "fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 flex items-center gap-3 px-5 py-3.5 rounded-xl border bg-zinc-900 text-sm font-semibold max-w-sm pointer-events-none shadow-2xl";
    }, 4000);
}

/**
 * Fetch and Render Feed Posts
 */
async function loadFeed() {
    const container = document.getElementById('postsTimeline');
    try {
        const res = await apiCall('posts', 'GET');
        if (res.status === 'success') {
            const posts = res.data;
            if (posts.length === 0) {
                container.innerHTML = `
                    <div class="glass-panel p-16 rounded-2xl border border-zinc-850 text-center flex flex-col items-center justify-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-zinc-900/80 border border-zinc-800 flex items-center justify-center text-zinc-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1">No posts found</h3>
                            <p class="text-zinc-500 text-sm">Be the first photographer to capture and share a premium moment!</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            posts.forEach(post => {
                const card = document.createElement('article');
                card.className = 'glass-panel rounded-2xl overflow-hidden border border-zinc-850/80 shadow-lg relative group transition-all duration-300 hover:border-amber-500/25 animate-fade-in';
                card.id = `post-card-${post.id}`;

                // User display name & details
                const authorAvatar = post.user.avatar ? getStorageUrl(post.user.avatar) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150';
                const postImage = post.image_url;
                const formattedDate = new Date(post.created_at).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                card.innerHTML = `
                    <!-- Header -->
                    <div class="px-5 py-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3.5">
                            <img class="w-10 h-10 rounded-full object-cover border border-zinc-800" src="${authorAvatar}" alt="${post.user.name}">
                            <div>
                                <span class="font-bold text-white hover:text-amber-400 cursor-pointer transition text-sm sm:text-base">${post.user.name}</span>
                                <div class="flex items-center gap-1.5 text-zinc-500 text-xs">
                                    <span>${formattedDate}</span>
                                    ${post.location ? `
                                        <span>•</span>
                                        <span class="flex items-center gap-0.5 text-amber-500/80 font-medium">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                            ${post.location}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delete Option (only for author) -->
                        ${currentUser && currentUser.id === post.user_id ? `
                            <button onclick="deletePost(${post.id})" class="text-zinc-500 hover:text-rose-500 p-2 rounded-full hover:bg-zinc-900 transition" title="Delete Frame">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        ` : ''}
                    </div>

                    <!-- Photo Display Container -->
                    <div class="relative bg-zinc-900 overflow-hidden cursor-pointer aspect-[4/3] group-hover:shadow-[inset_0_0_80px_rgba(0,0,0,0.5)] select-none" 
                         ondblclick="handleDoubleTap(event, ${post.id})">
                        <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-102" src="${postImage}" alt="Photography frame" loading="lazy">
                        
                        <!-- Liking pop element -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0" id="double-tap-heart-${post.id}">
                            <svg class="w-24 h-24 text-rose-500 filter drop-shadow-[0_0_20px_rgba(244,63,94,0.6)]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-5 pt-4 pb-3 flex items-center justify-between border-t border-zinc-900 bg-zinc-950/20">
                        <div class="flex items-center space-x-5">
                            <!-- Like Heart Toggle -->
                            <button id="like-btn-${post.id}" onclick="toggleLike(${post.id})" class="flex items-center gap-1.5 text-zinc-400 hover:text-white transition group/like">
                                <svg id="heart-icon-${post.id}" class="w-6 h-6 transition-all duration-300 transform group-hover/like:scale-110 ${post.user_has_liked ? 'text-rose-500 fill-rose-500 scale-105' : 'text-zinc-400 fill-none'}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                <span id="likes-count-${post.id}" class="text-sm font-semibold tracking-wide">${post.likes_count}</span>
                            </button>

                            <!-- Comments -->
                            <button onclick="openCommentsDrawer(${post.id})" class="flex items-center gap-1.5 text-zinc-400 hover:text-white transition group/comment">
                                <svg class="w-6 h-6 transition group-hover/comment:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                <span id="comments-count-${post.id}" class="text-sm font-semibold tracking-wide">${post.comments_count}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Caption Content -->
                    <div class="px-5 pb-5 flex flex-col gap-1.5">
                        <p class="text-sm leading-relaxed text-zinc-300">
                            <span class="font-extrabold text-white mr-1.5 cursor-pointer hover:underline">${post.user.name}</span>
                            ${post.caption || ''}
                        </p>
                        
                        <!-- Top Comments Preview (up to 2) -->
                        ${post.comments && post.comments.length > 0 ? `
                            <div class="mt-2 pt-2 border-t border-zinc-900/50 flex flex-col gap-1.5">
                                ${post.comments.slice(0, 2).map(c => `
                                    <div class="text-xs">
                                        <span class="font-bold text-white mr-1">${c.user.name}:</span>
                                        <span class="text-zinc-400">${c.content}</span>
                                    </div>
                                `).join('')}
                                ${post.comments.length > 2 ? `
                                    <button onclick="openCommentsDrawer(${post.id})" class="text-xs text-amber-500/80 font-medium hover:underline mt-0.5 text-left">
                                        View all ${post.comments.length} comments
                                    </button>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
                container.appendChild(card);
            });
        }
    } catch (err) {
        container.innerHTML = `<div class="glass-panel p-8 text-center text-rose-500 border border-rose-500/30">Failed to sync feed. Let's make sure the backend is active.</div>`;
    }
}

/**
 * Handle Double-Tap Heartbeat liking
 */
let lastTap = 0;
async function handleDoubleTap(event, postId) {
    const currentTime = new Date().getTime();
    const tapLength = currentTime - lastTap;
    if (tapLength < 300 && tapLength > 0) {
        // Trigger heartbeat pop visually
        const overlay = document.getElementById(`double-tap-heart-${postId}`);
        if (overlay) {
            overlay.classList.remove('opacity-0', 'heartbeat-active');
            void overlay.offsetWidth; // Force DOM reflow
            overlay.classList.add('heartbeat-active');
        }

        // Call API if not already liked
        const icon = document.getElementById(`heart-icon-${postId}`);
        if (icon && !icon.classList.contains('text-rose-500')) {
            await executeLikeToggle(postId);
        }
    }
    lastTap = currentTime;
}

/**
 * Action triggered on heart click
 */
async function toggleLike(postId) {
    await executeLikeToggle(postId);
}

/**
 * Generic API controller for Liking and Unliking
 */
async function executeLikeToggle(postId) {
    try {
        const res = await apiCall(`posts/${postId}/like`, 'POST');
        if (res.status === 'success') {
            const isLiked = res.liked;
            const likesCount = res.likes_count;

            // Update DOM Card UI elements
            const icon = document.getElementById(`heart-icon-${postId}`);
            const countSpan = document.getElementById(`likes-count-${postId}`);
            if (icon && countSpan) {
                countSpan.textContent = likesCount;
                if (isLiked) {
                    icon.classList.remove('text-zinc-400', 'fill-none');
                    icon.classList.add('text-rose-500', 'fill-rose-500', 'scale-105');
                } else {
                    icon.classList.remove('text-rose-500', 'fill-rose-500', 'scale-105');
                    icon.classList.add('text-zinc-400', 'fill-none');
                }
            }

            // Sync with Drawer if currently active
            if (currentActivePostId === postId) {
                document.getElementById('drawerLikesCount').textContent = `${likesCount} Likes`;
            }
        }
    } catch (err) {
        console.error('Like toggle failed', err);
    }
}

/**
 * Delete a post (only author)
 */
async function deletePost(postId) {
    if (!confirm('Are you sure you want to permanently delete this visual moment?')) return;
    try {
        const res = await apiCall(`posts/${postId}`, 'DELETE');
        if (res.status === 'success') {
            showToast('Post removed successfully!');
            loadFeed();
        } else {
            showToast(res.message || 'Unauthorized action.', false);
        }
    } catch (err) {
        showToast('Error removing post', false);
    }
}

/**
 * Toggle creator upload modal
 */
function toggleUploadModal(show) {
    const modal = document.getElementById('uploadModal');
    if (show) {
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0'), 10);
        setTimeout(() => modal.querySelector('div').classList.remove('scale-95'), 10);
    } else {
        modal.classList.add('opacity-0');
        modal.querySelector('div').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            resetImagePreview(null);
            document.getElementById('postUploadForm').reset();
        }, 300);
    }
}

/**
 * Local thumbnail rendering on photo pick
 */
function previewSelectedImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate size limit (10MB)
    if (file.size > 10 * 1024 * 1024) {
        showToast('File exceeds 10MB limit.', false);
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('uploaderPlaceholder').classList.add('hidden');
        document.getElementById('imagePreviewContainer').classList.remove('hidden');
        document.getElementById('imagePreview').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function resetImagePreview(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    document.getElementById('postImageInput').value = '';
    document.getElementById('imagePreview').src = '';
    document.getElementById('imagePreviewContainer').classList.add('hidden');
    document.getElementById('uploaderPlaceholder').classList.remove('hidden');
}

/**
 * Publish Creator Post
 */
async function handlePostSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('submitPostBtn');
    const prevBtnText = btn.innerHTML;

    const fileInput = document.getElementById('postImageInput');
    const captionInput = document.getElementById('postCaption');
    const locationInput = document.getElementById('postLocation');

    if (!fileInput.files[0]) {
        showToast('Please select a photograph to publish.', false);
        return;
    }

    // Build FormData
    const formData = new FormData();
    formData.append('image', fileInput.files[0]);
    formData.append('caption', captionInput.value);
    if (locationInput.value.trim()) {
        formData.append('location', locationInput.value.trim());
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="w-5 h-5 border-2 border-t-zinc-950 border-zinc-500 rounded-full animate-spin"></span> Publishing...';

    try {
        const res = await apiCall('posts', 'POST', formData);
        if (res.status === 'success' || res.statusCode === 210 || res.statusCode === 201 || res.statusCode === 200) {
            showToast('Premium frame published successfully!');
            toggleUploadModal(false);
            loadFeed();
        } else {
            showToast(res.message || 'Failed to publish post', false);
        }
    } catch (err) {
        showToast('Network error while uploading', false);
    } finally {
        btn.disabled = false;
        btn.innerHTML = prevBtnText;
    }
}

/**
 * Slide-over Comment drawer controllers
 */
async function openCommentsDrawer(postId) {
    currentActivePostId = postId;
    toggleCommentsDrawer(true);

    const drawerComments = document.getElementById('drawerCommentsList');
    drawerComments.innerHTML = '<div class="text-center py-12 text-zinc-500">Loading comments...</div>';

    try {
        const res = await apiCall('posts', 'GET');
        if (res.status === 'success') {
            const post = res.data.find(p => p.id === postId);
            if (post) {
                // Populate post preview in drawer header
                document.getElementById('drawerPostImage').src = post.image_url;
                document.getElementById('drawerPostAuthor').textContent = post.user.name;
                document.getElementById('drawerPostCaption').textContent = post.caption || '';
                document.getElementById('drawerLikesCount').textContent = `${post.likes_count} Likes`;

                // Render all comments
                if (post.comments && post.comments.length > 0) {
                    drawerComments.innerHTML = '';
                    post.comments.forEach(c => {
                        const div = document.createElement('div');
                        div.className = 'flex gap-3 text-sm animate-fade-in';
                        
                        const commentAvatar = c.user.avatar ? getStorageUrl(c.user.avatar) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100';
                        
                        div.innerHTML = `
                            <img class="w-8 h-8 rounded-full object-cover border border-zinc-800" src="${commentAvatar}" alt="${c.user.name}">
                            <div class="flex-grow min-w-0">
                                <div class="bg-zinc-900/60 border border-zinc-850 px-3.5 py-2.5 rounded-2xl">
                                    <p class="font-extrabold text-white mb-0.5">${c.user.name}</p>
                                    <p class="text-zinc-300 leading-relaxed">${c.content}</p>
                                </div>
                            </div>
                        `;
                        drawerComments.appendChild(div);
                    });
                } else {
                    drawerComments.innerHTML = `
                        <div class="text-center py-12 text-zinc-500 flex flex-col items-center gap-2">
                            <svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            <p class="text-sm font-semibold text-zinc-400">No comments yet</p>
                            <p class="text-xs text-zinc-600">Be the first to leave a premium comment!</p>
                        </div>
                    `;
                }
            }
        }
    } catch (err) {
        drawerComments.innerHTML = '<div class="text-center py-6 text-red-500">Failed to load comments</div>';
    }
}

function toggleCommentsDrawer(show) {
    const drawer = document.getElementById('commentsDrawer');
    const backdrop = document.getElementById('commentsDrawerBackdrop');
    if (show) {
        backdrop.classList.remove('hidden');
        drawer.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            drawer.classList.add('active');
        }, 10);
    } else {
        backdrop.classList.add('opacity-0');
        drawer.classList.remove('active');
        setTimeout(() => {
            backdrop.classList.add('hidden');
            drawer.classList.add('hidden');
            document.getElementById('commentSubmitForm').reset();
            currentActivePostId = null;
        }, 400);
    }
}

/**
 * Submit interactive comment
 */
async function handleCommentSubmit(event) {
    event.preventDefault();
    if (!currentActivePostId) return;

    const input = document.getElementById('commentInput');
    const content = input.value.trim();
    if (!content) return;

    try {
        const res = await apiCall(`posts/${currentActivePostId}/comment`, 'POST', { content });
        if (res.status === 'success') {
            input.value = '';
            // Refresh comments panel immediately
            await openCommentsDrawer(currentActivePostId);
            
            // Sync comment count on card
            const countSpan = document.getElementById(`comments-count-${currentActivePostId}`);
            if (countSpan) {
                // Fetch new timeline preview or increment
                const count = parseInt(countSpan.textContent) || 0;
                countSpan.textContent = count + 1;
            }
            showToast('Comment posted successfully!');
            // Reload feed in background to update feed previews
            loadFeed();
        } else {
            showToast(res.message || 'Failed to post comment', false);
        }
    } catch (err) {
        showToast('Error posting comment', false);
    }
}
