/**
 * Pulse - 感情共鳴型SNS
 * クライアントサイドスクリプト
 */

// 共鳴ボタンのトグル
async function toggleResonate(btn) {
    const postId = btn.dataset.postId;
    if (!postId) return;

    btn.disabled = true;

    try {
        const res = await fetch('api/resonate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: parseInt(postId) }),
        });

        const data = await res.json();

        if (data.success) {
            btn.classList.toggle('resonated', data.resonated);
            btn.querySelector('.resonate-count').textContent = data.count;

            // 共鳴エフェクト
            if (data.resonated) {
                createRippleEffect(btn);
            }
        }
    } catch (e) {
        console.error('共鳴エラー:', e);
    } finally {
        btn.disabled = false;
    }
}

// フォローボタンのトグル
async function toggleFollow(btn) {
    const userId = btn.dataset.userId;
    if (!userId) return;

    btn.disabled = true;

    try {
        const res = await fetch('api/follow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: parseInt(userId) }),
        });

        const data = await res.json();

        if (data.success) {
            btn.textContent = data.following ? 'フォロー中' : 'フォローする';
            btn.classList.toggle('btn-outline', data.following);
            btn.classList.toggle('btn-primary', !data.following);

            // フォロワー数の更新
            const followerStat = document.querySelector('.stat-value');
            if (followerStat) {
                followerStat.textContent = data.follower_count;
            }
        }
    } catch (e) {
        console.error('フォローエラー:', e);
    } finally {
        btn.disabled = false;
    }
}

// 波紋エフェクト
function createRippleEffect(element) {
    const card = element.closest('.post-card');
    if (!card) return;

    const ripple = document.createElement('div');
    ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: var(--mood-color, #6366f1);
        opacity: 0.15;
        pointer-events: none;
        animation: rippleExpand 0.8s ease-out forwards;
    `;

    const rect = element.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const x = rect.left - cardRect.left + rect.width / 2;
    const y = rect.top - cardRect.top + rect.height / 2;

    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.style.width = '0';
    ripple.style.height = '0';
    ripple.style.transform = 'translate(-50%, -50%)';

    card.appendChild(ripple);

    // アニメーション
    requestAnimationFrame(() => {
        ripple.style.width = '300px';
        ripple.style.height = '300px';
        ripple.style.opacity = '0';
        ripple.style.transition = 'all 0.8s ease-out';
    });

    setTimeout(() => ripple.remove(), 800);
}

// 返信フォームの表示切替
function toggleReplyForm(btn) {
    const postId = btn.dataset.postId;
    const wrap = document.getElementById('replyForm-' + postId);
    if (!wrap) return;
    wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
    if (wrap.style.display === 'block') {
        wrap.querySelector('.reply-textarea').focus();
    }
}

// 返信を送信
async function submitReply(btn, parentId) {
    const wrap = document.getElementById('replyForm-' + parentId);
    if (!wrap) return;

    const textarea = wrap.querySelector('.reply-textarea');
    const moodSelect = wrap.querySelector('.reply-mood-select');
    const content = textarea.value.trim();

    if (!content) return;

    btn.disabled = true;

    try {
        const res = await fetch('api/reply.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                parent_id: parentId,
                content: content,
                mood: moodSelect.value,
            }),
        });

        const data = await res.json();

        if (data.success) {
            textarea.value = '';
            wrap.style.display = 'none';

            // 返信数ボタンを更新または作成
            const card = btn.closest('.post-card');
            let showBtn = card.querySelector('.reply-show-btn');
            if (showBtn) {
                showBtn.querySelector('.reply-count-label').textContent = '返信を見る (' + data.reply_count + ')';
            } else {
                const actions = card.querySelector('.post-actions');
                showBtn = document.createElement('button');
                showBtn.className = 'reply-show-btn';
                showBtn.setAttribute('onclick', 'toggleReplies(this)');
                showBtn.dataset.postId = parentId;
                showBtn.innerHTML = '<span class="reply-count-label">返信を見る (' + data.reply_count + ')</span>';
                actions.appendChild(showBtn);
            }

            // 返信一覧を表示更新
            const repliesWrap = document.getElementById('replies-' + parentId);
            if (repliesWrap && repliesWrap.style.display === 'block') {
                loadReplies(parentId, repliesWrap);
            }
        }
    } catch (e) {
        console.error('返信エラー:', e);
    } finally {
        btn.disabled = false;
    }
}

// 返信一覧の表示切替
async function toggleReplies(btn) {
    const postId = btn.dataset.postId;
    const wrap = document.getElementById('replies-' + postId);
    if (!wrap) return;

    if (wrap.style.display === 'block') {
        wrap.style.display = 'none';
        return;
    }

    wrap.style.display = 'block';
    wrap.innerHTML = '<div class="replies-loading">読み込み中...</div>';
    await loadReplies(postId, wrap);
}

// 返信を読み込み
async function loadReplies(postId, wrap) {
    try {
        const res = await fetch('api/reply.php?post_id=' + postId);
        const data = await res.json();

        if (data.success) {
            if (data.count === 0) {
                wrap.innerHTML = '<div class="replies-empty">まだ返信はありません</div>';
            } else {
                wrap.innerHTML = data.html;
            }
        }
    } catch (e) {
        wrap.innerHTML = '<div class="replies-empty">読み込みに失敗しました</div>';
        console.error('返信読み込みエラー:', e);
    }
}

// 文字カウント
document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('content');
    const counter = document.getElementById('charCount');

    if (textarea && counter) {
        const updateCount = () => {
            counter.textContent = textarea.value.length;
        };
        textarea.addEventListener('input', updateCount);
        updateCount();
    }

    // タイムカプセルトグル
    const timecapsuleToggle = document.getElementById('timecapsuleToggle');
    const timecapsuleSettings = document.getElementById('timecapsuleSettings');

    if (timecapsuleToggle && timecapsuleSettings) {
        const toggle = () => {
            timecapsuleSettings.style.display = timecapsuleToggle.checked ? 'block' : 'none';
        };
        timecapsuleToggle.addEventListener('change', toggle);
        toggle();
    }

    // ムード選択時のビジュアルフィードバック
    const moodOptions = document.querySelectorAll('.mood-option input');
    moodOptions.forEach(input => {
        input.addEventListener('change', () => {
            const form = input.closest('.post-form');
            if (form) {
                const color = getComputedStyle(input.closest('.mood-option')).getPropertyValue('--mood-color');
                form.style.borderColor = color;
                form.style.boxShadow = `0 0 20px ${color}20`;
            }
        });
    });
});
