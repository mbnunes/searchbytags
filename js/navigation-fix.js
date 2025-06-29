// Corrige o link de navegação se necessário
document.addEventListener('DOMContentLoaded', function() {
    // Procura o item do menu
    const menuItems = document.querySelectorAll('#appmenu li, .app-menu-main li');
    menuItems.forEach(function(item) {
        if (item.dataset.id === 'search_by_tags' || item.dataset.appId === 'search_by_tags') {
            const link = item.querySelector('a');
            if (link && !link.href.includes('/apps/search_by_tags')) {
                link.href = OC.generateUrl('/apps/search_by_tags/');
            }
        }
    });
});