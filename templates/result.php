<div class="section">
    <h2>Resultados da Busca por Tags</h2>
    <div id="results" style="margin-top: 1em;"></div>
</div>

<script nonce="<?php print_unescaped($_['requesttoken']) ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const query = <?php echo json_encode($_['query']); ?>;
        const apiUrl = OC.generateUrl('/apps/search_by_tags/api/search') + '?query=' + encodeURIComponent(query);

        fetch(apiUrl)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('results');
                container.innerHTML = '';

                if (!data.files || data.files.length === 0) {
                    container.innerHTML = '<p>Nenhum arquivo encontrado com essas tags.</p>';
                    return;
                }

                data.files.forEach(file => {
                    const div = document.createElement('div');
                    div.classList.add('file-item');
                    div.style.marginBottom = '1em';
                    div.style.borderBottom = '1px solid #ccc';
                    div.style.paddingBottom = '1em';

                    const link = document.createElement('a');
                    link.href = OC.generateUrl('/apps/files') + '?dir=' + encodeURIComponent(file.path) + '&scrollto=' + encodeURIComponent(file.name);
                    link.target = '_blank';

                    link.innerHTML = `
                        <div style="font-weight:bold;">${file.name}</div>
                        <div style="font-size:smaller;">Tags: ${file.tags.join(', ')}</div>
                    `;

                    div.appendChild(link);
                    container.appendChild(div);
                });
            })
            .catch(error => {
                document.getElementById('results').innerHTML = '<p>Erro ao buscar arquivos.</p>';
                console.error('Erro na busca:', error);
            });
    });
</script>
