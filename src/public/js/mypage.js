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
});