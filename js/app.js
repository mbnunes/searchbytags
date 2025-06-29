(function() {
    'use strict';

    let selectedTags = [];
    let allTags = [];

    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });

    function initializeApp() {
        loadAllTags();
        setupEventListeners();
    }

    function setupEventListeners() {
        const searchInput = document.getElementById('tag-search-input');
        const searchButton = document.getElementById('search-button');
        const suggestions = document.getElementById('tag-suggestions');

        searchInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            if (value.length > 0) {
                showSuggestions(value);
            } else {
                suggestions.classList.add('hidden');
            }
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const value = e.target.value.trim();
                if (value) {
                    addTag(value);
                    e.target.value = '';
                    suggestions.classList.add('hidden');
                }
            }
        });

        searchButton.addEventListener('click', performSearch);

        // Clique fora das sugestões
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box')) {
                suggestions.classList.add('hidden');
            }
        });
    }

    async function loadAllTags() {
        try {
            const response = await fetch(OC.generateUrl('/apps/tagssearch/api/tags'));
            const data = await response.json();
            allTags = data.tags || [];
        } catch (error) {
            console.error('Erro ao carregar tags:', error);
        }
    }

    function showSuggestions(searchTerm) {
        const suggestions = document.getElementById('tag-suggestions');
        const filteredTags = allTags.filter(tag => 
            tag.name.toLowerCase().includes(searchTerm.toLowerCase()) &&
            !selectedTags.includes(tag.name)
        );

        if (filteredTags.length === 0) {
            suggestions.classList.add('hidden');
            return;
        }

        suggestions.innerHTML = '';
        filteredTags.slice(0, 5).forEach(tag => {
            const div = document.createElement('div');
            div.className = 'tag-suggestion';
            div.textContent = tag.name;
            div.addEventListener('click', function() {
                addTag(tag.name);
                document.getElementById('tag-search-input').value = '';
                suggestions.classList.add('hidden');
            });
            suggestions.appendChild(div);
        });

        suggestions.classList.remove('hidden');
    }

    function addTag(tagName) {
        if (!selectedTags.includes(tagName)) {
            selectedTags.push(tagName);
            updateSelectedTags();
        }
    }

    function removeTag(tagName) {
        selectedTags = selectedTags.filter(tag => tag !== tagName);
        updateSelectedTags();
    }

    function updateSelectedTags() {
        const container = document.getElementById('selected-tags');
        container.innerHTML = '';
        
        selectedTags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'selected-tag';
            span.innerHTML = `
                ${escapeHtml(tag)}
                <span class="remove-tag" data-tag="${escapeHtml(tag)}">×</span>
            `;
            container.appendChild(span);
        });

        // Adiciona eventos de remoção
        container.querySelectorAll('.remove-tag').forEach(elem => {
            elem.addEventListener('click', function() {
                removeTag(this.dataset.tag);
            });
        });
    }

    async function performSearch() {
        if (selectedTags.length === 0) {
            OC.Notification.show('Selecione pelo menos uma tag para buscar');
            return;
        }

        const loading = document.getElementById('loading');
        const results = document.getElementById('search-results');
        
        loading.classList.remove('hidden');
        results.innerHTML = '';

        try {
            const response = await fetch(OC.generateUrl('/apps/tagssearch/api/search') + 
                '?tags=' + encodeURIComponent(selectedTags.join(',')));
            const data = await response.json();
            
            loading.classList.add('hidden');
            
            if (data.files && data.files.length > 0) {
                displayResults(data.files);
            } else {
                results.innerHTML = '<p class="no-results">Nenhum arquivo encontrado com as tags selecionadas.</p>';
            }
        } catch (error) {
            loading.classList.add('hidden');
            console.error('Erro na busca:', error);
            OC.Notification.show('Erro ao buscar arquivos');
        }
    }

    function displayResults(files) {
        const results = document.getElementById('search-results');
        results.innerHTML = '<h3>Resultados da busca (' + files.length + ' arquivos)</h3>';
        
        const table = document.createElement('table');
        table.className = 'files-table';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tags</th>
                    <th>Tamanho</th>
                    <th>Modificado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;
        
        const tbody = table.querySelector('tbody');
        
        files.forEach(file => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="filename">
                    <span class="icon icon-${file.type}"></span>
                    ${escapeHtml(file.name)}
                </td>
                <td class="tags">
                    ${file.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join(' ')}
                </td>
                <td>${formatFileSize(file.size)}</td>
                <td>${formatDate(file.mtime)}</td>
                <td>
                    <a href="${file.url}" class="button">Abrir</a>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        results.appendChild(table);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function formatDate(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
})();