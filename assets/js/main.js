/**
 * TI Stock – Scripts Personalizados
 */

document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------
    // Toggle da Sidebar
    // ----------------------------------------
    const sidebar       = document.getElementById('sidebar');
    const toggleBtn     = document.getElementById('sidebarToggle');
    const STORAGE_KEY   = 'tistock_sidebar_collapsed';

    // Restaura estado salvo
    if (localStorage.getItem(STORAGE_KEY) === '1' && sidebar) {
        sidebar.classList.add('collapsed');
    }

    function atualizarBotao() {
        if (!toggleBtn) return;
        const collapsed = sidebar.classList.contains('collapsed');
        const icon = toggleBtn.querySelector('i');
        if (icon) {
            icon.className = collapsed ? 'fas fa-indent' : 'fas fa-bars';
        }
        toggleBtn.title = collapsed ? 'Expandir menu' : 'Recolher menu';
    }

    // Aplica estado inicial do botão
    atualizarBotao();

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(
                STORAGE_KEY,
                sidebar.classList.contains('collapsed') ? '1' : '0'
            );
            atualizarBotao();
        });
    }

    // ----------------------------------------
    // Confirmação de exclusão
    // ----------------------------------------
    document.querySelectorAll('.btn-excluir').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const nome = this.dataset.nome || 'este item';
            if (!confirm('Confirma a exclusão de "' + nome + '"?\n\nEsta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });

    // ----------------------------------------
    // Auto-dismiss de alertas após 5 segundos
    // ----------------------------------------
    document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 5000);
    });

    // ----------------------------------------
    // Destaque da linha ao clicar (acessibilidade)
    // ----------------------------------------
    document.querySelectorAll('table.table tbody tr').forEach(function (row) {
        row.style.cursor = 'default';
    });

});
