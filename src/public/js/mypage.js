// タブ切り替え機能
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanels = document.querySelectorAll('.tab-panel');

    // タブ切り替え関数
    function switchTab(pageParam) {
        let targetTab;
        if (pageParam === 'sell') {
            targetTab = 'listed';   // 出品した商品
        } else if (pageParam === 'buy') {
            targetTab = 'purchased'; // 購入した商品
        } else if (pageParam === 'trade') {
            targetTab = 'trading'; // 取引中の商品
        } else {
            targetTab = 'listed';   // デフォルト
        }

        // 全てのタブボタンとパネルから active クラスを削除
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabPanels.forEach(panel => panel.classList.remove('active'));

        // 対応するタブボタンとパネルに active クラスを追加
        const targetButton = document.querySelector(`[data-tab="${targetTab}"]`);
        const targetPanel = document.getElementById(targetTab);
        
        if (targetButton && targetPanel) {
            targetButton.classList.add('active');
            targetPanel.classList.add('active');
        }
    }

    // タブボタンクリック時の処理
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // タブに対応するページパラメータを設定
            let pageParam;
            if (targetTab === 'listed') {
                pageParam = 'sell';  // 出品した商品
            } else if (targetTab === 'purchased') {
                pageParam = 'buy';   // 購入した商品
            } else if (targetTab === 'trading') {
                pageParam = 'trade'; // 取引中の商品
            }

            // タブ切り替え
            switchTab(pageParam);
            
            // URLにクエリパラメータを追加
            if (pageParam) {
                const newUrl = `${window.location.pathname}?page=${pageParam}`;
                window.history.pushState({page: pageParam}, '', newUrl);
            }
        });
    });

    // ブラウザの戻る/進むボタン対応
    window.addEventListener('popstate', function(event) {
        const urlParams = new URLSearchParams(window.location.search);
        const pageParam = urlParams.get('page') || 'sell';
        switchTab(pageParam);
    });

    // ユーザー名の自動縮小（折り返し無しで幅にフィットさせる）
    const userNameEl = document.querySelector('.profile-info__name');
    if (userNameEl) {
        const minFontPx = 16;

        const getMaxFontPx = () => {
            if (userNameEl.dataset.maxFontPx) {
                const v = Number(userNameEl.dataset.maxFontPx);
                if (!Number.isNaN(v) && v > 0) return v;
            }
            const computed = window.getComputedStyle(userNameEl);
            const px = Number.parseFloat(computed.fontSize);
            if (!Number.isNaN(px) && px > 0) {
                userNameEl.dataset.maxFontPx = String(px);
                return px;
            }
            return 36;
        };

        const fits = () => {
            return userNameEl.scrollWidth <= userNameEl.clientWidth;
        };

        const applyFontPx = (px) => {
            userNameEl.style.fontSize = `${px}px`;
        };

        const fitToWidth = () => {
            const maxPx = Math.max(minFontPx, Math.floor(getMaxFontPx()));

            applyFontPx(maxPx);

            if (userNameEl.clientWidth === 0) return;
            if (fits()) return;

            let low = minFontPx;
            let high = maxPx;
            while (low < high) {
                const mid = Math.ceil((low + high) / 2);
                applyFontPx(mid);
                if (fits()) {
                    low = mid;
                } else {
                    high = mid - 1;
                }
            }
            applyFontPx(low);
        };

        requestAnimationFrame(fitToWidth);

        let rafId = null;
        const scheduleFit = () => {
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(() => {
                rafId = null;
                fitToWidth();
            });
        };
        window.addEventListener('resize', scheduleFit);

        if ('ResizeObserver' in window) {
            const ro = new ResizeObserver(scheduleFit);
            ro.observe(userNameEl);
            if (userNameEl.parentElement) ro.observe(userNameEl.parentElement);
        }
    }
});