document.addEventListener('DOMContentLoaded', function() {
    // Adiciona campo de busca por tags
    const searchBox = document.querySelector('.searchbox');
    if (searchBox) {
        const tagSearchInput = document.createElement('input');
        tagSearchInput.type = 'text';
        tagSearchInput.placeholder = 'Buscar por tags...';
        tagSearchInput.className = 'tag-search-input';
        
        tagSearchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value;
            if (searchTerm.length > 2) {
                searchByTags(searchTerm);
            }
        }, 300));
        
        searchBox.appendChild(tagSearchInput);
    }
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

async function searchByTags(tagName) {
    try {
        const response = await fetch(OC.generateUrl('/apps/tagssearch/api/search/' + encodeURIComponent(tagName)));
        const data = await response.json();
        
        if (data.files) {
            displaySearchResults(data.files);
        }
    } catch (error) {
        console.error('Erro na busca por tags:', error);
    }
}

function displaySearchResults(files) {
    // Implementar a exibição dos resultados
    // Você pode integrar com a interface do Files ou criar sua própria
    console.log('Arquivos encontrados:', files);
}