/**
 * ============================================================
 *  Pulse - 感情共鳴型SNS
 *  クライアントサイドスクリプト（app.js）
 * ============================================================
 *
 * 【このファイルの役割】
 *   - 共鳴ボタン・フォローボタン・返信機能の非同期処理（Ajax）
 *   - 波紋エフェクトなどのアニメーション
 *   - 投稿ページの文字数カウンタ・タイムカプセル設定の表示切替
 *
 * 【ポイント】
 *   - サーバー通信は fetch() + async/await で書く（モダンな書き方）
 *   - DOM 操作は素の JavaScript（ライブラリ非依存）
 *
 * 【初学者向けの読み方】
 *   1. toggleResonate() と toggleFollow() で「ボタン → fetch → JSON → 画面更新」の流れを見る
 *   2. submitReply() / loadReplies() で、返信の送信と取得を追う
 *   3. createRippleEffect() で、JavaScript が一時的な HTML 要素を作って演出する方法を見る
 *   4. DOMContentLoaded の中で、ページ読み込み後にイベントを設定する流れを確認する
 */

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken(),
    };
}

// =====================================================
//  共鳴ボタンのトグル（ON ⇄ OFF）
//
//  - HTML 側: <button onclick="toggleResonate(this)" data-post-id="..." />
//  - 仕組み:
//    1. 押されたボタンの data-post-id を取得
//    2. /api/resonate.php に POST して状態を切り替え
//    3. レスポンスに合わせて見た目（クラス、件数）を更新
//    4. 共鳴になった瞬間は波紋エフェクトを再生
// =====================================================
async function toggleResonate(btn) {
    const postId = btn.dataset.postId;   // data-post-id 属性
    if (!postId) return;

    // 連打防止: 通信中はボタンを無効化
    btn.disabled = true;

    try {
        // fetch でサーバーに POST リクエスト
        const res = await fetch('api/resonate.php', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ post_id: parseInt(postId) }),
        });

        // レスポンス（JSON）をオブジェクトにパース
        const data = await res.json();

        if (data.success) {
            // resonated クラスを ON/OFF（CSS でハイライト切替）
            btn.classList.toggle('resonated', data.resonated);
            // 件数表示を最新の数字に更新
            btn.querySelector('.resonate-count').textContent = data.count;

            // 共鳴になった瞬間だけ波紋アニメーションを発動
            if (data.resonated) {
                createRippleEffect(btn);
            }
        }
    } catch (e) {
        // 通信エラー時はコンソールに出すだけ（UI は元の状態に戻る）
        console.error('共鳴エラー:', e);
    } finally {
        // 成否に関わらずボタンを必ず再び押せるように
        btn.disabled = false;
    }
}

// =====================================================
//  フォローボタンのトグル
// =====================================================
async function toggleFollow(btn) {
    const userId = btn.dataset.userId;
    if (!userId) return;

    btn.disabled = true;

    try {
        const res = await fetch('api/follow.php', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ user_id: parseInt(userId) }),
        });

        const data = await res.json();

        if (data.success) {
            // ボタンのラベルとスタイルを切替
            btn.textContent = data.following ? 'フォロー中' : 'フォローする';
            btn.classList.toggle('btn-outline', data.following);
            btn.classList.toggle('btn-primary', !data.following);

            // プロフィールページの「フォロワー数」を即時反映
            //   ※ 1つ目の .stat-value がフォロワー数の前提
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

// =====================================================
//  波紋エフェクト（共鳴時のアニメーション）
//
//  仕組み:
//    1. ボタンを含む .post-card を親として取得
//    2. 円形の <div> を動的に生成し、その上に重ねる
//    3. ボタン中心位置に配置し、CSS の transition で大きく拡大
//    4. 0.8 秒後に DOM から削除
// =====================================================
function createRippleEffect(element) {
    const card = element.closest('.post-card');   // 直近の親 .post-card
    if (!card) return;

    // 円形の波紋要素を作成
    const ripple = document.createElement('div');
    ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: var(--mood-color, #6366f1);   /* 投稿のムード色を引用 */
        opacity: 0.15;
        pointer-events: none;                       /* クリック判定を貫通させる */
        animation: rippleExpand 0.8s ease-out forwards;
    `;

    // 押されたボタンの中心座標を、カード基準の (x, y) に変換
    const rect     = element.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const x        = rect.left - cardRect.left + rect.width  / 2;
    const y        = rect.top  - cardRect.top  + rect.height / 2;

    // 初期サイズ 0、中心に配置
    ripple.style.left      = x + 'px';
    ripple.style.top       = y + 'px';
    ripple.style.width     = '0';
    ripple.style.height    = '0';
    ripple.style.transform = 'translate(-50%, -50%)';   // 自身の中心を基準点にする

    card.appendChild(ripple);

    // 描画フレームの直後にサイズ・透明度を変更 → CSS transition で滑らかに広がる
    //   requestAnimationFrame: ブラウザの再描画タイミングを待ってから実行する
    requestAnimationFrame(() => {
        ripple.style.width      = '300px';
        ripple.style.height     = '300px';
        ripple.style.opacity    = '0';
        ripple.style.transition = 'all 0.8s ease-out';
    });

    // アニメーション後に DOM から削除（メモリの無駄遣いを防ぐ）
    setTimeout(() => ripple.remove(), 800);
}

// =====================================================
//  返信フォームの表示切替
// =====================================================
function toggleReplyForm(btn) {
    const postId = btn.dataset.postId;
    const wrap   = document.getElementById('replyForm-' + postId);
    if (!wrap) return;

    // 表示なら隠す、非表示なら出す（三項演算子）
    wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';

    // 表示直後にテキストエリアにフォーカス（すぐ入力できる）
    if (wrap.style.display === 'block') {
        wrap.querySelector('.reply-textarea').focus();
    }
}

// =====================================================
//  返信を送信する
// =====================================================
async function submitReply(btn, parentId) {
    const wrap = document.getElementById('replyForm-' + parentId);
    if (!wrap) return;

    const textarea   = wrap.querySelector('.reply-textarea');
    const moodSelect = wrap.querySelector('.reply-mood-select');
    const content    = textarea.value.trim();

    // 空文字なら送信しない
    if (!content) return;

    btn.disabled = true;

    try {
        const res = await fetch('api/reply.php', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({
                parent_id: parentId,
                content:   content,
                mood:      moodSelect.value,
            }),
        });

        const data = await res.json();

        if (data.success) {
            // 入力欄をクリアしてフォームを閉じる
            textarea.value = '';
            wrap.style.display = 'none';

            // 「返信を見る (n)」ボタンを更新 or 新規作成
            const card    = btn.closest('.post-card');
            let showBtn = card.querySelector('.reply-show-btn');

            if (showBtn) {
                // すでに存在 → 件数だけ書き換え
                showBtn.querySelector('.reply-count-label').textContent =
                    '返信を見る (' + data.reply_count + ')';
            } else {
                // 0件 → 1件目になったタイミングでボタンを動的に挿入
                const actions = card.querySelector('.post-actions');
                showBtn = document.createElement('button');
                showBtn.className = 'reply-show-btn';
                showBtn.setAttribute('onclick', 'toggleReplies(this)');
                showBtn.dataset.postId = parentId;
                showBtn.innerHTML =
                    '<span class="reply-count-label">返信を見る (' + data.reply_count + ')</span>';
                actions.appendChild(showBtn);
            }

            // 返信一覧が開いている場合は再読込して新しい返信を反映
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

// =====================================================
//  返信一覧の表示切替
// =====================================================
async function toggleReplies(btn) {
    const postId = btn.dataset.postId;
    const wrap   = document.getElementById('replies-' + postId);
    if (!wrap) return;

    // 既に開いていたら閉じるだけ
    if (wrap.style.display === 'block') {
        wrap.style.display = 'none';
        return;
    }

    // 開く: 一旦ローディングメッセージを出してから読み込み
    wrap.style.display = 'block';
    wrap.innerHTML = '<div class="replies-loading">読み込み中...</div>';
    await loadReplies(postId, wrap);
}

// =====================================================
//  返信一覧をサーバーから取得して描画
// =====================================================
async function loadReplies(postId, wrap) {
    try {
        const res = await fetch('api/reply.php?post_id=' + postId);
        const data = await res.json();

        if (data.success) {
            if (data.count === 0) {
                wrap.innerHTML = '<div class="replies-empty">まだ返信はありません</div>';
            } else {
                // サーバー側で組み立て済みの HTML をそのまま流し込む
                wrap.innerHTML = data.html;
            }
        }
    } catch (e) {
        wrap.innerHTML = '<div class="replies-empty">読み込みに失敗しました</div>';
        console.error('返信読み込みエラー:', e);
    }
}

// =====================================================
//  ページ読み込み完了後に走る初期化処理
//
//  DOMContentLoaded:
//    HTML が解析され DOM が構築された時点で発火するイベント。
//    （画像などの読み込みは待たない → 早く実行できる）
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
    // -------- 文字数カウンタ（投稿ページ） --------
    const textarea = document.getElementById('content');
    const counter  = document.getElementById('charCount');

    if (textarea && counter) {
        const updateCount = () => {
            counter.textContent = textarea.value.length;
        };
        textarea.addEventListener('input', updateCount);
        updateCount();   // 初期表示時にも一度実行
    }

    // -------- タイムカプセル設定の表示切替 --------
    const timecapsuleToggle   = document.getElementById('timecapsuleToggle');
    const timecapsuleSettings = document.getElementById('timecapsuleSettings');

    if (timecapsuleToggle && timecapsuleSettings) {
        const toggle = () => {
            timecapsuleSettings.style.display = timecapsuleToggle.checked ? 'block' : 'none';
        };
        timecapsuleToggle.addEventListener('change', toggle);
        toggle();   // 初期状態を反映
    }

    // -------- ムード選択時に投稿フォームをそのムードの色に変える --------
    const moodOptions = document.querySelectorAll('.mood-option input');
    moodOptions.forEach(input => {
        input.addEventListener('change', () => {
            const form = input.closest('.post-form');
            if (form) {
                // CSS 変数 --mood-color の値を取得（HTML の style 属性で個別設定済み）
                const color = getComputedStyle(input.closest('.mood-option'))
                                .getPropertyValue('--mood-color');
                form.style.borderColor = color;
                // 末尾の '20' は α=0x20（透明度）。柔らかいネオン感を出す
                form.style.boxShadow = `0 0 20px ${color}20`;
            }
        });
    });
});
