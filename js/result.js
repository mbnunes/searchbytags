OCP.Loader.loadScript('viewer', 'viewer');

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

			// Chama a função após criar todos os cards
			setupTooltips();

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
			setupTooltips();
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
				setupTooltips();
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
				setupTooltips();
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

		// Event delegation - adiciona listener apenas uma vez no container
		if (!tagResults.hasAttribute('data-click-handler')) {
			tagResults.setAttribute('data-click-handler', 'true');

			tagResults.addEventListener('click', function (e) {

				const link = e.target.closest('.file-link');
				if (!link) return;
				e.preventDefault();

				console.log('Click funcionou!');

				const card = link.closest('.file-card');
				const fileId = card.getAttribute('data-file-id');
				const filePath = card.getAttribute('data-file-path');
				const fileName = card.getAttribute('data-file-name');
				const mimeType = card.getAttribute('data-mime-type');

				const isImage = mimeType.startsWith('image/');
				const isVideo = mimeType.startsWith('video/');

				if (isImage || isVideo) {

					// Abre o viewer diretamente
					if (OCA.Viewer) {
						OCA.Viewer.open({
							fileId: parseInt(fileId),
							path: filePath + '/' + fileName,
							list: getFileListForViewer(), // função para criar lista de arquivos
							canLoop: true
						});
					} else {
						console.error('Viewer não está disponível');
						// Fallback para o método antigo
						window.location.href = `${OC.getRootPath()}/apps/files/files/${fileId}?dir=${filePath}&openfile=true`;
					}
				} else {
					// Para outros arquivos, abre normalmente
					window.location.href = `${OC.getRootPath()}/apps/files/files/${fileId}?dir=${filePath}`;
				}
			});
		}

		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageFiles = totalFiles.slice(startIndex, endIndex);

		// Prepara lista para o viewer
		const fileList = [];
		pageFiles.forEach(file => {
			fileList.push({
				id: file.id,
				name: file.name,
				mime: file.mime || file.mimetype || 'application/octet-stream',
				mimetype: file.mimetype || file.mime || 'application/octet-stream',
				path: file.path === '/' ? '/' + file.name : file.path + '/' + file.name,
				size: file.size || 0,
				etag: file.etag || '',
				permissions: file.permissions || 31,
				type: file.type || 'file',
				hasPreview: true,
				isImage: file.isImage || (file.mimetype && file.mimetype.startsWith('image/')),
				directory: file.path || '/'
			});
		});

		pageFiles.forEach((file, index) => {
			const item = document.createElement('div');
			item.className = 'file-card';

			// Armazena os dados do arquivo no card
			item.setAttribute('data-file-id', file.id);
			item.setAttribute('data-file-path', file.path);
			item.setAttribute('data-file-name', file.name); // ADICIONE ESTA LINHA
			item.setAttribute('data-mime-type', file.mimetype || file.mime || '');

			const link = document.createElement('a');
			link.href = '#';
			link.className = 'file-link';
			// NÃO adicione addEventListener aqui

			const img = document.createElement('img');
			img.src = OC.generateUrl(`/core/preview?fileId=${file.id}&x=128&y=128`);
			img.alt = file.name;
			img.className = 'thumbnail';

			// Adiciona loading lazy para performance
			img.loading = 'lazy';

			// Fallback se a preview falhar
			img.onerror = function () {
				this.src = OC.imagePath('core', 'filetypes/file');
			};

			const name = document.createElement('div');
			name.className = 'filename';
			name.title = file.name;
			name.textContent = shortenFilename(file.name);

			const date = document.createElement('div');
			date.className = 'filedate';
			date.textContent = formatDate(file.mtime);

			// Criar tooltip com metadados expandidos
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

			// Tipo MIME
			const tooltipMime = document.createElement('div');
			tooltipMime.className = 'tooltip-row';
			tooltipMime.innerHTML = `<span class="tooltip-label">Tipo:</span> ${file.mime || file.mimetype}`;
			tooltip.appendChild(tooltipMime);

			// Proprietário
			if (file.owner) {
				const tooltipOwner = document.createElement('div');
				tooltipOwner.className = 'tooltip-row';
				tooltipOwner.innerHTML = `<span class="tooltip-label">Proprietário:</span> ${file.owner}`;
				tooltip.appendChild(tooltipOwner);
			}

			// Dimensões da imagem (se for imagem)
			if (file.isImage && file.width && file.height) {
				const tooltipDimensions = document.createElement('div');
				tooltipDimensions.className = 'tooltip-row';
				tooltipDimensions.innerHTML = `<span class="tooltip-label">Dimensões:</span> ${file.width} × ${file.height} pixels`;
				tooltip.appendChild(tooltipDimensions);
			}

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

			// Adiciona elementos ao link
			link.appendChild(img);
			link.appendChild(name);
			link.appendChild(date);

			// Adiciona link e tooltip ao card
			item.appendChild(link);
			item.appendChild(tooltip);

			// Adiciona o card aos resultados
			tagResults.appendChild(item);
		});

		// Scroll para o topo dos resultados
		tagResults.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	// Após o loop forEach que cria os cards, adicione:

function setupTooltips() {
    const fileCards = document.querySelectorAll('.file-card');
    
    fileCards.forEach(card => {
        const tooltip = card.querySelector('.file-tooltip');
        if (!tooltip) return; // Verifica se o tooltip existe
        
        card.addEventListener('mouseenter', function(e) {
            // Obtém as dimensões e posição do card
            const cardRect = this.getBoundingClientRect();
            
            // Primeiro posiciona o tooltip invisível para calcular dimensões
            tooltip.style.display = 'block';
            tooltip.style.opacity = '0';
            
            // Aguarda o próximo frame para garantir que as dimensões sejam calculadas
            requestAnimationFrame(() => {
                const tooltipRect = tooltip.getBoundingClientRect();
                
                // Calcula a posição X (centralizada)
                let left = cardRect.left + (cardRect.width / 2) - (tooltipRect.width / 2);
                
                // Calcula a posição Y (acima do card por padrão)
                let top = cardRect.top - tooltipRect.height - 8;
                
                // Verifica se há espaço suficiente acima
                if (top < 10) {
                    // Se não houver espaço acima, posiciona abaixo
                    top = cardRect.bottom + 8;
                    tooltip.classList.add('tooltip-below');
                } else {
                    tooltip.classList.remove('tooltip-below');
                }
                
                // Ajusta se sair da tela pela direita
                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                
                // Ajusta se sair da tela pela esquerda
                if (left < 10) {
                    left = 10;
                }
                
                // Aplica as posições
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
                
                // Mostra o tooltip com fade in
                tooltip.style.opacity = '1';
            });
        });
        
        card.addEventListener('mouseleave', function(e) {
            const tooltip = this.querySelector('.file-tooltip');
            if (tooltip) {
                tooltip.style.opacity = '0';
                // Aguarda a transição terminar antes de esconder
                setTimeout(() => {
                    tooltip.style.display = 'none';
                }, 300); // Mesmo tempo da transição no CSS
            }
        });
    });
}



// Se você carregar mais cards dinamicamente (paginação), 
// chame setupTooltips() novamente após adicionar novos cards

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

function getFileListForViewer() {
    // Retorna array com todos os arquivos da página atual que podem ser visualizados
    const viewableFiles = [];
    
    document.querySelectorAll('.file-card').forEach(card => {
        const mimeType = card.getAttribute('data-mime-type');
        if (mimeType && (mimeType.startsWith('image/') || mimeType.startsWith('video/'))) {
            viewableFiles.push({
                fileId: parseInt(card.getAttribute('data-file-id')),
                path: card.getAttribute('data-file-path') + '/' + card.getAttribute('data-file-name'),
                name: card.getAttribute('data-file-name'),
                mime: mimeType,
                hasPreview: true
            });
        }
    });
    
    return viewableFiles;
}