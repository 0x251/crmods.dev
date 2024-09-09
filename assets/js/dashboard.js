document.addEventListener('DOMContentLoaded', () => {
    const modsPerPage = 9;
    let currentPage = 1;
    let modsData = [];

    function renderModsPage(page) {
        const start = (page - 1) * modsPerPage;
        const end = start + modsPerPage;
        const modsToRender = modsData.slice(start, end);

        const yourModsContainer = document.getElementById('your-mods');
        yourModsContainer.innerHTML = '';
        const row = document.createElement('div');
        row.className = 'columns is-multiline';
        modsToRender.forEach(mod => {
            if (mod.mod_logo && mod.mod_name && mod.short_description && mod.tags) {
                const modElement = document.createElement('div');
                modElement.className = 'column is-one-third';
                
                modElement.innerHTML = DOMPurify.sanitize(`
                    <div class="box mod-box boxer" style="background-color: #2e2e2e; border-radius: 10px;">
                        <article class="media">
                            <div class="media-left">
                                <figure class="image is-64x64">
                                    <img src="${mod.mod_logo}" alt="Mod">
                                </figure>
                            </div>
                            <div class="media-content">
                                <div class="content">
                                    <p>
                                        <strong style="color: #fff;">${mod.mod_name.length > 10 ? mod.mod_name.substring(0, 10) + '...' : mod.mod_name}</strong>
                                        ${mod.verified == 1 ? '<span style="color: #1e90ff; float: right;"><i class="fas fa-check"></i></span>' : ''}
                                        <br>
                                        <span style="color: gray;">by ${mod.author.length > 10 ? mod.author.substring(0, 10) + '...' : mod.author}</span>
                                        <br><br>
                                        <span style="color: #fff;" class="is-size-7 description" style="overflow: hidden; text-overflow: ellipsis; white-space: normal; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; word-break: break-word;">${mod.short_description.length > 43 ? mod.short_description.substring(0, 43) + '...' : mod.short_description}</span>
                                    </p>
                                    <div class="download-info" style="display: flex; align-items: center; margin-top: 10px;">
                                        <img src="data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M16 18C16 18.5523 15.5523 19 15 19L5.00002 19C4.44774 19 4.00002 18.5523 4.00002 18C4.00002 17.4477 4.44774 17 5.00002 17L15 17C15.5523 17 16 17.4477 16 18ZM10.6247 13.7809C10.2595 14.073 9.74055 14.073 9.37533 13.7809L4.37533 9.78087C3.94407 9.43586 3.87415 8.80657 4.21916 8.3753C4.56417 7.94404 5.19346 7.87412 5.62472 8.21913L9.00002 10.9194L9.00002 2C9.00002 1.44772 9.44774 0.999999 10 0.999999C10.5523 1 11 1.44772 11 2L11 10.9194L14.3753 8.21913C14.8066 7.87412 15.4359 7.94404 15.7809 8.37531C16.1259 8.80657 16.056 9.43586 15.6247 9.78087L10.6247 13.7809Z' fill='%23999999'/%3E%3C/svg%3E%0A" style="margin-right: 5px;">
                                        <span style="color: #fff;">${mod.download_count >= 1000000 ? (mod.download_count / 1000000).toFixed(1) + 'm' : mod.download_count >= 1000 ? (mod.download_count / 1000).toFixed(1) + 'k' : mod.download_count}</span>
                                        ${window.innerWidth <= 768 ? mod.tags.split(',').slice(0, 2).map(tag => `<span class="mod-tag" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${DOMPurify.sanitize(tag.replace(/"/g, ''))}</span>`).join('') : mod.tags.split(',').map(tag => `<span class="mod-tag" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${DOMPurify.sanitize(tag.replace(/"/g, ''))}</span>`).join('')}
                                    </div>
                                    <div class="buttons" style="display: flex; justify-content: flex-end; display: none; margin-top: 40px;">
                                        <a class="button is-light edit" href="#" style="background-color: #1e90ff; color: #fff; display: none;" data-mod='${JSON.stringify(mod)}'><i class="fas fa-edit"></i> Edit</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                `);
                row.appendChild(modElement);
            }
        });
        yourModsContainer.appendChild(row);
        const columns = document.querySelectorAll('.column');
        columns.forEach(column => {
           
            column.addEventListener('mouseover', () => {
                if (window.innerWidth > 768) {
                    column.classList.add('animate__animated', 'animate__pulse');
                }
                column.querySelector('.edit').style.display = 'inline-block';
                column.querySelector('.buttons').style.display = 'flex';
                column.querySelector('.description').style.whiteSpace = 'normal';
                column.querySelector('.description').style.overflow = 'visible';
                column.querySelector('.description').style.textOverflow = 'clip';
                column.querySelector('.description').style.display = 'block';
                column.querySelector('.description').textContent = column.querySelector('.description').dataset.fullText;
                column.querySelector('.download-info').style.marginTop = '0';
                column.querySelector('.mod-box').style.height = `${column.querySelector('.mod-box').offsetHeight}px`;
            });
            column.addEventListener('mouseout', () => {
                if (window.innerWidth > 768) {
                    column.classList.remove('animate__animated', 'animate__pulse');
                }
                column.querySelector('.edit').style.display = 'none';
                column.querySelector('.buttons').style.display = 'none';
                column.querySelector('.description').style.whiteSpace = 'normal';
                column.querySelector('.description').style.overflow = 'hidden';
                column.querySelector('.description').style.textOverflow = 'ellipsis';
                column.querySelector('.description').style.display = '-webkit-box';
                column.querySelector('.description').style.webkitBoxOrient = 'vertical';
                column.querySelector('.description').style.webkitLineClamp = '3';
                column.querySelector('.description').textContent = column.querySelector('.description').dataset.shortText;
                column.querySelector('.download-info').style.marginTop = '10px';
                column.querySelector('.mod-box').style.height = 'auto';
            });
        });

        const descriptions = document.querySelectorAll('.description');
        descriptions.forEach(description => {
            const fullText = description.textContent;
            const shortText = fullText.length > 50 ? fullText.substring(0, 50) + '...' : fullText;
            description.dataset.fullText = fullText;
            description.dataset.shortText = shortText;
            description.textContent = shortText;
            description.style.overflow = 'hidden';
            description.style.textOverflow = 'ellipsis';
            description.style.whiteSpace = 'normal';
            description.style.display = '-webkit-box';
            description.style.webkitBoxOrient = 'vertical';
            description.style.webkitLineClamp = '3';
            description.style.wordBreak = 'break-word';
        });

        const modBoxes = document.querySelectorAll('.mod-box');
        let maxHeight = 0;
        modBoxes.forEach(box => {
            const boxHeight = box.offsetHeight;
            if (boxHeight > maxHeight) {
                maxHeight = boxHeight;
            }
        });
        modBoxes.forEach(box => {
            box.style.height = `${maxHeight}px`;
        });

        const editButtons = document.querySelectorAll('.edit');
        editButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const modData = JSON.parse(button.getAttribute('data-mod'));
                document.getElementById('edit-mod-name').value = modData.mod_name;
                document.getElementById('edit-short-description-input').value = modData.short_description;
                document.getElementById('edit-long-description-input').value = modData.long_description;
                document.getElementById('edit-mod-id').value = modData.mod_id;

         

                

                document.getElementById('edit-modal').classList.add('is-active');
            });
        });
    }

    function renderPagination(totalMods) {
        const totalPages = Math.ceil(totalMods / modsPerPage);
        const paginationContainer = document.getElementById('pagination');
        paginationContainer.innerHTML = '';
        paginationContainer.style.display = 'flex';
        paginationContainer.style.justifyContent = 'center';

        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.className = 'pagination-button';
            pageButton.textContent = i;
            pageButton.style.marginTop = '30px';
            pageButton.style.backgroundColor = 'transparent';
            pageButton.style.color = '#fff';
            pageButton.style.textAlign = 'center';
            pageButton.style.border = 'none';
            pageButton.style.cursor = 'pointer';
            pageButton.style.fontSize = '1.2em';
            pageButton.addEventListener('click', () => {
                currentPage = i;
                renderModsPage(currentPage);
            });
            paginationContainer.appendChild(pageButton);
        }
    }

    fetch('/dashboard/your-mods')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modsData = data.mods;
                document.getElementById('mods-submitted').textContent = data.total_count;
                document.getElementById('mods-verified').textContent = data.verified_count;
                renderModsPage(currentPage);
                renderPagination(modsData.length);
            }
        });
});