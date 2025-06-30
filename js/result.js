document.addEventListener('DOMContentLoaded', async function () {
    const input = document.getElementById('tag-input');
    const tagResults = document.getElementById('tag-results');
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('query');

    // Preenche o input se já tem query na URL
    if (query) {
        input.value = query;
        await loadResults(query);
    }

    // AutoComplete simples
    input.addEventListener('input', async function () {
        const val = input.value.trim();
        if (val.length >= 2) {
            const tagSuggestions = await fetchTags(val);
            input.setAttribute('list', 'tag-suggestions');

            let datalist = document.getElementById('tag-suggestions');
            if (!datalist) {
                datalist = document.createElement('datalist');
                datalist.id = 'tag-suggestions';
                document.body.appendChild(datalist);
            }

            datalist.innerHTML = '';
            tagSuggestions.forEach(tag => {
                const opt = document.createElement('option');
                opt.value = tag.name;
                datalist.appendChild(opt);
            });
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            window.location.href = OC.generateUrl('/apps/search_by_tags/') + '?query=' + encodeURIComponent(input.value);
        }
    });

    async function fetchTags(filter) {
        try {
            const response = await fetch(OC.generateUrl('/ocs/v2.php/apps/files/api/v1/tags') + '?format=json', {
                headers: {
                    'OCS-APIREQUEST': 'true'
                }
            });
            const data = await response.json();
            return data.ocs.data.filter(tag => tag.name.toLowerCase().includes(filter.toLowerCase()));
        } catch (err) {
            console.error('Erro ao buscar tags:', err);
            return [];
        }
    }

    async function loadResults(query) {
        try {
            const res = await fetch(OC.generateUrl('/apps/search_by_tags/api/search') + '?query=' + encodeURIComponent(query));
            const data = await res.json();

            tagResults.innerHTML = '';

            if (!data.files || data.files.length === 0) {
                tagResults.innerHTML = '<p>Nenhum arquivo encontrado com essas tags.</p>';
                return;
            }

            const container = document.createElement('ul');
            container.className = 'fileListView';
            container.style.display = 'flex';
            container.style.flexWrap = 'wrap';
            container.style.gap = '1.5em';

            data.files.forEach(file => {
                const item = document.createElement('li');
                item.className = 'file';
                item.style.listStyle = 'none';
                item.style.width = '160px';

                const link = document.createElement('a');
                link.href = OC.generateUrl('/apps/files') + '?dir=' + encodeURIComponent(file.path) + '&scrollto=' + encodeURIComponent(file.name);
                link.className = 'filename';
                link.style.display = 'block';
                link.style.textAlign = 'center';

                const thumb = document.createElement('img');
                thumb.src = OC.generateUrl(`/apps/files/api/v1/thumbnail/${file.fileid}/256`);
                thumb.alt = file.name;
                thumb.className = 'thumbnail';
                thumb.style.width = '100%';
                thumb.style.borderRadius = '8px';

                const title = document.createElement('div');
                title.textContent = file.name;
                title.style.marginTop = '0.5em';
                title.style.fontSize = '0.9em';
                title.style.wordBreak = 'break-word';

                link.appendChild(thumb);
                link.appendChild(title);
                item.appendChild(link);
                container.appendChild(item);
            });

            tagResults.appendChild(container);
        } catch (error) {
            console.error('Erro ao buscar arquivos por tag:', error);
            tagResults.innerHTML = '<p>Erro ao buscar arquivos.</p>';
        }
    }
});
