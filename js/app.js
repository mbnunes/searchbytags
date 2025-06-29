(function() {
    'use strict';

    let allTags = [];
    let currentQuery = '';

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
        const clearButton = document.getElementById('clear-button');
        const suggestions = document.getElementById('tag-suggestions');

        // Busca ao pressionar Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        // Preview da busca e sugestões
        searchInput.addEventListener('input', function(e) {
            const value = e.target.value;
            updateSearchPreview(value);
            
            // Mostra sugestões para a última palavra digitada
            const words = value.split(/\s+/);
            const lastWord = words[words.length - 1];
            
            if (lastWord && lastWord.toUpperCase() !== 'AND' && lastWord.toUpperCase() !== 'OR') {
                showSuggestions(lastWord, value);
            } else {
                suggestions.classList.add('hidden');
            }
        });

        searchButton.addEventListener('click', performSearch);
        
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            document.getElementById('search-preview').innerHTML = '';
            document.getElementById('search-results').innerHTML = '';
            currentQuery = '';
        });

        // Clique fora das sugestões
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box')) {
                suggestions.classList.add('hidden');
            }
        });
    }

    async function loadAllTags() {
        try {
            const response = await fetch(OC.generateUrl('/apps/search_by_tags/api/tags'));
            const data = await response.json();
            allTags = data.tags || [];
            console.log('Tags carregadas:', allTags.length);
        } catch (error) {
            console.error('Erro ao carregar tags:', error);
        }
    }

    function showSuggestions(searchTerm, fullValue) {
        const suggestions = document.getElementById('tag-suggestions');
        
        const filteredTags = allTags.filter(tag => 
            tag.name.toLowerCase().includes(searchTerm.toLowerCase())
        );

        if (filteredTags.length === 0) {
            suggestions.classList.add('hidden');
            return;
        }

        suggestions.innerHTML = '';
        filteredTags.slice(0, 10).forEach(tag => {
            const div = document.createElement('div');
            div.className = 'tag-suggestion';
            div.textContent = tag.name;
            div.addEventListener('click', function() {
                const input = document.getElementById('tag-search-input');
                const words = input.value.split(/\s+/);
                words[words.length - 1] = tag.name;
                input.value = words.join(' ');
                input.focus();
                suggestions.classList.add('hidden');
                updateSearchPreview(input.value);
            });
            suggestions.appendChild(div);
        });

        suggestions.classList.remove('hidden');
    }

    function updateSearchPreview(query) {
        const preview = document.getElementById('search-preview');
        if (!query.trim()) {
            preview.innerHTML = '';
            return;
        }

        const parsed = parseQuery(query);
        let previewHtml = '<div class="preview-label">Buscar por:</div>';
        
        parsed.groups.forEach((group, index) => {
            if (index > 0) {
                previewHtml += '<span class="operator or">OU</span>';
            }
            
            if (group.tags.length > 1) {
                previewHtml += '<span class="group">(';
            }
            
            group.tags.forEach((tag, tagIndex) => {
                if (tagIndex > 0) {
                    previewHtml += '<span class="operator and">E</span>';
                }
                previewHtml += `<span class="tag-preview">${escapeHtml(tag)}</span>`;
            });
            
            if (group.tags.length > 1) {
                previewHtml += ')</span>';
            }
        });
        
        preview.innerHTML = previewHtml;
    }

    function parseQuery(query) {
        const orParts = query.split(/\s+OR\s+/i);
        const groups = [];
        
        orParts.forEach(orPart => {
            const andParts = orPart.split(/\s+AND\s+/i);
            const tags = andParts.map(t => t.trim()).filter(t => t);
            if (tags.length > 0) {
                groups.push({ tags });
            }
        });
        
        return { groups };
    }

    async function performSearch() {
        const input = document.getElementById('tag-search-input');
        const query = input.value.trim();
        
        if (!query) {
            OC.Notification.show(t('search_by_tags', 'Digite uma busca'));
            return;
        }
        
        currentQuery = query;
        
        const loading = document.getElementById('loading');
        const results = document.getElementById('search-results');
        
        loading.classList.remove('hidden');
        results.innerHTML = '';

        try {
            console.log('Buscando:', query);
            
            const params = new URLSearchParams({
                query: query
            });
            
            const response = await fetch(OC.generateUrl('/apps/search_by_tags/api/search') + '?' + params);
            const data = await response.json();
            
            loading.classList.add('hidden');
            
            if (data.error) {
                OC.Notification.show(t('search_by_tags', 'Erro: ') + data.error);
                return;
            }
            
            if (data.files && data.files.length > 0) {
                displayResults(data.files);
            } else {
                const parsed = parseQuery(query);
                let explanation = '<div class="no-results">';
                explanation += '<p>Nenhum arquivo encontrado para a busca:</p>';
                explanation += '<div class="query-explanation">';
                
                parsed.groups.forEach((group, index) => {
                    if (index > 0) {
                        explanation += ' <strong>OU</strong> ';
                    }
                    if (group.tags.length > 1) {
                        explanation += 'arquivos com TODAS as tags: ' + group.tags.map(t => `<em>${escapeHtml(t)}</em>`).join(' <strong>E</strong> ');
                    } else {
                        explanation += 'arquivos com a tag: <em>' + escapeHtml(group.tags[0]) + '</em>';
                    }
                });
                
                explanation += '</div>';
                explanation += '<p class="hint">Verifique se as tags estão escritas corretamente.</p>';
                explanation += '</div>';
                
                results.innerHTML = explanation;
            }
        } catch (error) {
            loading.classList.add('hidden');
            console.error('Erro na busca:', error);
            OC.Notification.show(t('search_by_tags', 'Erro ao buscar arquivos'));
        }
    }

    function displayResults(files) {
        const results = document.getElementById('search-results');
        
        let html = `
            <div class="results-header">
                <h3>${files.length} arquivo${files.length !== 1 ? 's' : ''} encontrado${files.length !== 1 ? 's' : ''}</h3>
                <p class="results-query">Busca: <code>${escapeHtml(currentQuery)}</code></p>
            </div>
        `;
        
        html += '<table class="files-table">';
        html += `
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Localização</th>
                    <th>Tags</th>
                    <th>Tamanho</th>
                    <th>Modificado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
        `;
        
        files.forEach(file => {
            const fileIcon = file.type === 'folder' ? 'icon-folder' : getMimeIcon(file.mimetype);
            html += `
                <tr>
                    <td class="filename">
                        <span class="${fileIcon}"></span>
                        ${escapeHtml(file.name)}
                    </td>
                    <td class="filepath">${escapeHtml(file.path || '/')}</td>
                    <td class="tags">
                        ${file.tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join(' ')}
                    </td>
                    <td class="filesize">${formatFileSize(file.size)}</td>
                    <td class="modified">${formatDate(file.mtime)}</td>
                    <td class="actions">
                        <a href="${file.url}" class="button primary">
                            <span class="icon-folder-open"></span>
                            Abrir
                        </a>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        results.innerHTML = html;
    }

    function getMimeIcon(mimetype) {
        if (mimetype.startsWith('image/')) return 'icon-picture';
        if (mimetype.startsWith('video/')) return 'icon-video';
        if (mimetype.startsWith('audio/')) return 'icon-audio';
        if (mimetype.includes('pdf')) return 'icon-file-pdf';
        if (mimetype.includes('text')) return 'icon-file-text';
        return 'icon-file';
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
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('pt-BR', options);
    }

    // Função helper para tradução
    function t(app, text) {
        return text; // Simplificado - o Nextcloud injeta a função real
    }
})();