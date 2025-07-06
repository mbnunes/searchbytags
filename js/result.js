document.addEventListener('DOMContentLoaded', async function () {
	const input = document.getElementById('tag-input');
	const tagResults = document.getElementById('tag-results');

	// Variáveis de paginação
	let currentPage = 1;
	let itemsPerPage = 20;
	let totalFiles = [];
	let currentQuery = '';

	let debounceTimeout;

	input.addEventListener('input', function () {
		clearTimeout(debounceTimeout);

		debounceTimeout = setTimeout(async () => {
			const val = input.value.trim();
			currentQuery = val;
			currentPage = 1; // Reset para primeira página ao fazer nova busca

			if (val.length >= 2) {
				const tags = await fetchTags();
				const datalist = document.getElementById('tag-suggestions');
				datalist.innerHTML = '';
				tags.forEach(tag => {
					const opt = document.createElement('option');
					opt.value = tag.name;
					datalist.appendChild(opt);
				});
			}
			await loadResults(val);
			await renderTagFolders(val);
			history.replaceState(null, '', OC.generateUrl('/apps/search_by_tags/') + '?query=' + encodeURIComponent(val));
		}, 300);
	});

	const urlParams = new URLSearchParams(window.location.search);
	const query = urlParams.get('query');
	if (query) {
		input.value = query;
		currentQuery = query;
		await loadResults(query);
		await renderTagFolders(query);
	}

	async function fetchTags() {
		let allTags = [];

		try {
			const response = await fetch(OC.generateUrl('/apps/search_by_tags/search/getAllTags'), {
				headers: { 'OCS-APIREQUEST': 'true' },
				credentials: 'include'
			});

			const data = await response.json();

			if (data.tags) {
				allTags = data.tags;
			} else {
				console.warn('Resposta inesperada:', data);
			}
		} catch (err) {
			console.error('Erro ao buscar todas as tags:', err);
		}

		return allTags;
	}

	async function loadResults(query) {
		try {
			console.log('Carregando resultados para:', query); // Debug

			const res = await fetch(OC.generateUrl('/apps/search_by_tags/api/search') + '?query=' + encodeURIComponent(query));
			const data = await res.json();

			tagResults.innerHTML = '';

			if (!data.files || data.files.length === 0) {
				tagResults.innerHTML = '<div class="item">Nenhum arquivo encontrado.</div>';
				return;
			}

			// Armazena todos os arquivos
			totalFiles = data.files;
			console.log('Total de arquivos encontrados:', totalFiles.length); // Debug

			// Cria controles de paginação
			createPaginationControls();

			// Renderiza apenas os arquivos da página atual
			renderPage();

		} catch (err) {
			console.error('Erro ao carregar arquivos:', err);
		}
	}

	function createPaginationControls() {
    // Remove controles antigos se existirem
    const existingControls = document.querySelector('.pagination-controls');
    if (existingControls) {
        existingControls.remove();
    }

    const controls = document.createElement('div');
    controls.className = 'pagination-controls';

    // Seletor de quantidade por página
    const perPageContainer = document.createElement('div');
    perPageContainer.className = 'per-page-container';
    
    const perPageLabel = document.createElement('label');
    perPageLabel.textContent = 'Itens por página: ';
    perPageLabel.setAttribute('for', 'items-per-page');
    
    const perPageSelect = document.createElement('select');
    perPageSelect.id = 'items-per-page';
    perPageSelect.className = 'per-page-select';
    
    [10, 20, 50, 100].forEach(num => {
        const option = document.createElement('option');
        option.value = num;
        option.textContent = num;
        if (num === itemsPerPage) option.selected = true;
        perPageSelect.appendChild(option);
    });
    
    perPageSelect.addEventListener('change', (e) => {
        itemsPerPage = parseInt(e.target.value);
        currentPage = 1;
        renderPage();
        updatePaginationButtons();
    });
    
    perPageContainer.appendChild(perPageLabel);
    perPageContainer.appendChild(perPageSelect);

    // Container dos botões de navegação
    const navContainer = document.createElement('div');
    navContainer.className = 'pagination-nav';

    // Botão anterior
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn prev-btn';
    prevBtn.textContent = '← Anterior';
    prevBtn.onclick = () => {
        if (currentPage > 1) {
            currentPage--;
            renderPage();
            updatePaginationButtons();
        }
    };

    // Informação da página
    const pageInfo = document.createElement('span');
    pageInfo.className = 'page-info';
    updatePageInfo(pageInfo);

    // Botão próximo
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn next-btn';
    nextBtn.textContent = 'Próximo →';
    nextBtn.onclick = () => {
        const totalPages = Math.ceil(totalFiles.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderPage();
            updatePaginationButtons();
        }
    };

    navContainer.appendChild(prevBtn);
    navContainer.appendChild(pageInfo);
    navContainer.appendChild(nextBtn);

    controls.appendChild(perPageContainer);
    controls.appendChild(navContainer);

    // Insere no wrapper específico
    const wrapper = document.querySelector('.pagination-controls-wrapper');
    if (wrapper) {
        wrapper.appendChild(controls);
    }

    updatePaginationButtons();
}

	function updatePageInfo(pageInfo) {
		if (!pageInfo) {
			pageInfo = document.querySelector('.page-info');
		}
		const totalPages = Math.ceil(totalFiles.length / itemsPerPage);
		const startItem = (currentPage - 1) * itemsPerPage + 1;
		const endItem = Math.min(currentPage * itemsPerPage, totalFiles.length);

		pageInfo.textContent = `${startItem}-${endItem} de ${totalFiles.length} arquivos (Página ${currentPage} de ${totalPages})`;
	}

	function updatePaginationButtons() {
		const prevBtn = document.querySelector('.prev-btn');
		const nextBtn = document.querySelector('.next-btn');
		const pageInfo = document.querySelector('.page-info');

		const totalPages = Math.ceil(totalFiles.length / itemsPerPage);

		prevBtn.disabled = currentPage === 1;
		nextBtn.disabled = currentPage === totalPages;

		updatePageInfo(pageInfo);
	}

	function renderPage() {
    tagResults.innerHTML = '';

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageFiles = totalFiles.slice(startIndex, endIndex);

    // Prepara lista para o viewer
    const fileList = [];
    pageFiles.forEach(file => {
        fileList.push({
            id: file.id,
            name: file.name,
            mime: file.mime || 'image/jpeg',
            path: file.path + '/' + file.name,
            size: file.size || 0,
            etag: file.etag || '',
            permissions: 1,
            type: 'file',
            directory: file.path
        });
    });

    pageFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'file-card';

        const link = document.createElement('a');
        link.href = '#';
        link.className = 'file-link';
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (OCA?.Viewer?.open) {
                OCA.Viewer.open(fileList, index);
            }
        });

        const img = document.createElement('img');
        img.src = OC.generateUrl(`/core/preview?fileId=${file.id}&x=128&y=128`);
        img.alt = file.name;
        img.className = 'thumbnail';

        const name = document.createElement('div');
        name.className = 'filename';
        name.title = file.name;
        name.textContent = shortenFilename(file.name);

        const date = document.createElement('div');
        date.className = 'filedate';
        date.textContent = formatDate(file.mtime);

        // Criar tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'file-tooltip';
        
        // Nome completo do arquivo
        const tooltipName = document.createElement('div');
        tooltipName.className = 'tooltip-row';
        tooltipName.innerHTML = `<span class="tooltip-label">Nome:</span> ${file.name}`;
        tooltip.appendChild(tooltipName);

        // Tamanho do arquivo
        const tooltipSize = document.createElement('div');
        tooltipSize.className = 'tooltip-row';
        tooltipSize.innerHTML = `<span class="tooltip-label">Tamanho:</span> ${formatFileSize(file.size)}`;
        tooltip.appendChild(tooltipSize);

        // Data de modificação
        const tooltipDate = document.createElement('div');
        tooltipDate.className = 'tooltip-row';
        tooltipDate.innerHTML = `<span class="tooltip-label">Modificado:</span> ${formatDate(file.mtime)}`;
        tooltip.appendChild(tooltipDate);

        // Tags associadas
        if (file.tags && file.tags.length > 0) {
            const tooltipTags = document.createElement('div');
            tooltipTags.className = 'tooltip-row';
            tooltipTags.innerHTML = '<span class="tooltip-label">Tags:</span>';
            
            const tagsContainer = document.createElement('div');
            tagsContainer.className = 'tooltip-tags';
            
            file.tags.forEach(tag => {
                const tagSpan = document.createElement('span');
                tagSpan.className = 'tooltip-tag';
                tagSpan.textContent = tag;
                tagsContainer.appendChild(tagSpan);
            });
            
            tooltipTags.appendChild(tagsContainer);
            tooltip.appendChild(tooltipTags);
        }

        link.appendChild(img);
        link.appendChild(name);
        link.appendChild(date);

        item.appendChild(link);
        item.appendChild(tooltip);

        tagResults.appendChild(item);
    });

    // Scroll para o topo dos resultados
    tagResults.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Função auxiliar para formatar tamanho de arquivo
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

	async function renderTagFolders(query) {
		const tagFoldersContainer = document.getElementById('tag-folders');
		tagFoldersContainer.innerHTML = '';

		if (!query) return;

		const operators = ['and', 'or'];
		const tagParts = query
			.split(/\s+/)
			.map(t => t.trim())
			.filter(t => t && !operators.includes(t.toLowerCase()));

		if (tagParts.length === 0) return;

		let allTags = await fetchTags();

		tagParts.forEach(inputTag => {
			const match = allTags.find(tag => tag.name.toLowerCase() === inputTag.toLowerCase());
			if (match) {
				const tagLink = document.createElement('a');
				tagLink.className = 'tag-link';
				tagLink.href = OC.generateUrl(`/apps/files/tags/${match.id}?dir=/${match.id}`);

				const icon = document.createElement('span');
				icon.className = 'icon-tag';
				icon.textContent = '🏷️';

				const label = document.createElement('span');
				label.textContent = match.name;

				tagLink.appendChild(icon);
				tagLink.appendChild(label);
				tagFoldersContainer.appendChild(tagLink);
			}
		});
	}
});

function shortenFilename(filename, maxLength = 30) {
	if (filename.length <= maxLength) {
		return filename;
	}

	const extIndex = filename.lastIndexOf('.');
	const namePart = filename.substring(0, extIndex);
	const extPart = filename.substring(extIndex);

	const keep = Math.floor((maxLength - extPart.length - 3) / 2);
	return namePart.substring(0, keep) + '...' + namePart.substring(namePart.length - keep) + extPart;
}

function formatDate(timestamp) {
	if (!timestamp) return '';
	const date = new Date(timestamp * 1000);
	return date.toLocaleString('pt-BR', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit'
	});
}