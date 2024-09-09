document.addEventListener('DOMContentLoaded', () => {
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

    if ($navbarBurgers.length > 0) {
        $navbarBurgers.forEach(el => {
            el.addEventListener('click', () => {
                const target = el.dataset.target;
                const $target = document.getElementById(target);
                el.classList.toggle('is-active');
                $target.classList.toggle('is-active');
            });
        });
    }

    fetch('/authed')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const authButtons = document.getElementById('auth-buttons');
                authButtons.innerHTML = '<a class="button is-light" href="/dashboard" style="background-color: #1e90ff; color: #fff;">Dashboard</a>';
            }
        });

    const loadMods = (filterTag = null, page = 1, sortBy = 'default', versionSort = 'asc', searchQuery = '') => {
        fetch('/get-mods')
            .then(response => response.json())
            .then(mods => {
                const modListContainer = document.getElementById('mod-list');
                modListContainer.innerHTML = '';

                let filteredMods = filterTag && filterTag !== 'all' ? mods.filter(mod => mod.tags.includes(filterTag)) : mods;

                if (sortBy === 'popular') {
                    filteredMods = filteredMods.sort((a, b) => b.download_count - a.download_count);
                }

                if (versionSort === 'asc') {
                    filteredMods = filteredMods.sort((a, b) => a.version.localeCompare(b.version, undefined, { numeric: true }));
                } else if (versionSort === 'desc') {
                    filteredMods = filteredMods.sort((a, b) => b.version.localeCompare(a.version, undefined, { numeric: true }));
                }

                if (searchQuery) {
                    filteredMods = filteredMods.filter(mod => mod.mod_name.toLowerCase().includes(searchQuery.toLowerCase()));
                }

                const modsPerPage = 9;
                const totalPages = Math.ceil(filteredMods.length / modsPerPage);
                const startIndex = (page - 1) * modsPerPage;
                const endIndex = startIndex + modsPerPage;
                const modsToDisplay = filteredMods.slice(startIndex, endIndex);

                modsToDisplay.forEach(mod => {
                    const modElement = document.createElement('div');
                    modElement.className = 'column is-one-third';
                    modElement.style.transition = 'transform 0.3s ease; margin-top: 20px;';
                    modElement.innerHTML = `
                        <div class="box boxer" style="background-color: #2e2e2e; border-radius: 10px; overflow: hidden;">
                            <article class="media">
                                <div class="media-left">
                                    <figure class="image is-64x64">
                                        <img src="${mod.mod_logo}" alt="Mod">
                                    </figure>
                                </div>
                                <div class="media-content">
                                    <div class="content">
                                        <p>
                                            <strong style="color: #fff;">${DOMPurify.sanitize(mod.mod_name.length > 20 ? mod.mod_name.substring(0, 20) + '...' : mod.mod_name)}</strong>
                                            <br>
                                            <span style="color: gray;">by ${DOMPurify.sanitize(mod.author.length > 20 ? mod.author.substring(0, 20) + '...' : mod.author)}</span>
                                            <br><br>
                                            <span style="color: #fff;" class="is-size-7 description" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; word-break: break-all;">${DOMPurify.sanitize(mod.short_description.length > 43 ? mod.short_description.substring(0, 43) + '...' : mod.short_description)}</span>
                                        </p>
                                        <div class="download-info" style="display: flex; align-items: center; margin-top: 10px;">
                                            <img src="data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M16 18C16 18.5523 15.5523 19 15 19L5.00002 19C4.44774 19 4.00002 18.5523 4.00002 18C4.00002 17.4477 4.44774 17 5.00002 17L15 17C15.5523 17 16 17.4477 16 18ZM10.6247 13.7809C10.2595 14.073 9.74055 14.073 9.37533 13.7809L4.37533 9.78087C3.94407 9.43586 3.87415 8.80657 4.21916 8.3753C4.56417 7.94404 5.19346 7.87412 5.62472 8.21913L9.00002 10.9194L9.00002 2C9.00002 1.44772 9.44774 0.999999 10 0.999999C10.5523 1 11 1.44772 11 2L11 10.9194L14.3753 8.21913C14.8066 7.87412 15.4359 7.94404 15.7809 8.37531C16.1259 8.80657 16.056 9.43586 15.6247 9.78087L10.6247 13.7809Z' fill='%23999999'/%3E%3C/svg%3E%0A" style="margin-right: 5px;">
                                            <span style="color: #fff;">${mod.download_count >= 1000000 ? (mod.download_count / 1000000).toFixed(1) + 'm' : mod.download_count >= 1000 ? (mod.download_count / 1000).toFixed(1) + 'k' : mod.download_count}</span>
                                            ${window.innerWidth <= 768 ? mod.tags.split(',').slice(0, 2).map(tag => `<span class="mod-tag" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${DOMPurify.sanitize(tag.replace(/"/g, ''))}</span>`).join('') : mod.tags.split(',').map(tag => `<span class="mod-tag" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${DOMPurify.sanitize(tag.replace(/"/g, ''))}</span>`).join('')}
                                        </div>
                                        <div class="buttons" style="display: flex; justify-content: flex-end; display: none; margin-top: 40px;">
                                            <a class="button is-light more-info" href="#" style="background-color: #ff8c00; color: #fff; display: none; margin-right: 10px;" data-target="modal-${mod.mod_id}">More info</a>
                                            <a class="button is-light download" id="download-button" href="/mod/${mod.mod_id}" style="background-color: #1e90ff; color: #fff; display: none;" data-target="${mod.mod_id}">Download</a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    `;

                    if (window.innerWidth > 768) {
                        const modal = document.createElement('div');
                        modal.className = 'modal';
                        modal.id = `modal-${mod.mod_id}`;
                        modal.innerHTML = `
                            <div class="modal-background"></div>
                            <div class="modal-content">
                                <div class="box" style="background-color: #2e2e2e; border-radius: 10px;">
                                    <article class="media">
                                        <div class="media-left">
                                            <figure class="image is-64x64">
                                                <img src="${mod.mod_logo}" alt="Mod">
                                            </figure>
                                        </div>
                                        <div class="media-content">
                                            <div class="content">
                                                <p>
                                                    <strong style="color: #fff;">${DOMPurify.sanitize(mod.mod_name.length > 20 ? mod.mod_name.substring(0, 20) + '...' : mod.mod_name)}</strong>
                                                    <br>
                                                    <span style="color: gray;">by ${DOMPurify.sanitize(mod.author.length > 20 ? mod.author.substring(0, 20) + '...' : mod.author)}</span>
                                                    <br><br>
                                                    <span style="color: #fff; white-space: normal; word-break: break-all;" class="is-size-7">${DOMPurify.sanitize(mod.short_description)}</span>
                                                    <br><br>
                                                    <span class="mod-tag">${DOMPurify.sanitize(mod.version)}</span>
                                                    ${mod.tags.split(',').map(tag => `<span class="mod-tag" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${DOMPurify.sanitize(tag.replace(/"/g, ''))}</span>`).join('')}
                                                </p>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>
                            <button class="modal-close is-large" aria-label="close"></button>
                        `;
                        document.body.appendChild(modal);

                        modal.querySelector('.modal-close').addEventListener('click', () => {
                            modal.classList.remove('is-active');
                        });
                    } else {
                        modElement.addEventListener('click', () => {
                            window.location.href = `/mod/${mod.mod_id}`;
                        });
                    }

                    modListContainer.appendChild(modElement);
                });

                const columns = document.querySelectorAll('.column');
                columns.forEach(column => {
                    if (window.innerWidth > 768) {
                        column.addEventListener('mouseover', () => {
                            column.classList.add('animate__animated', 'animate__pulse');
                            column.querySelector('.more-info').style.display = 'inline-block';
                            column.querySelector('.download').style.display = 'inline-block';
                            column.querySelector('.buttons').style.display = 'flex';
                            column.querySelector('.description').style.whiteSpace = 'normal';
                            column.querySelector('.description').style.overflow = 'visible';
                            column.querySelector('.description').style.textOverflow = 'clip';
                            column.querySelector('.description').style.display = 'block';
                            column.querySelector('.description').textContent = column.querySelector('.description').dataset.fullText;
                            column.querySelector('.download-info').style.marginTop = '0';
                            column.querySelector('.description').style.wordBreak = 'break-all';
                        });
                        column.addEventListener('mouseout', () => {
                            column.classList.remove('animate__animated', 'animate__pulse');
                            column.querySelector('.more-info').style.display = 'none';
                            column.querySelector('.download').style.display = 'none';
                            column.querySelector('.buttons').style.display = 'none';
                            column.querySelector('.description').style.whiteSpace = 'normal';
                            column.querySelector('.description').style.overflow = 'hidden';
                            column.querySelector('.description').style.textOverflow = 'ellipsis';
                            column.querySelector('.description').style.display = 'block';
                            column.querySelector('.description').style.webkitBoxOrient = 'vertical';
                            column.querySelector('.description').style.webkitLineClamp = '3';
                            column.querySelector('.description').textContent = column.querySelector('.description').dataset.shortText;
                            column.querySelector('.download-info').style.marginTop = '10px';
                        });
                    }
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
                    description.style.webkitLineClamp = '4';
                    description.style.wordBreak = 'break-all';
                });

                const moreInfoButtons = document.querySelectorAll('.more-info');
                moreInfoButtons.forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        const targetModal = document.getElementById(button.dataset.target);
                        document.querySelectorAll('.modal').forEach(modal => {
                            modal.classList.remove('is-active');
                        });
                        targetModal.classList.add('is-active');
                        targetModal.querySelector('.modal-content').innerHTML = `
                            <div class="box" style="background-color: #2e2e2e; border-radius: 10px;">
                                <article class="media">
                                    <div class="media-left">
                                        <figure class="image is-64x64">
                                            <img src="${mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).mod_logo}" alt="Mod">
                                        </figure>
                                    </div>
                                    <div class="media-content">
                                        <div class="content">
                                            <p>
                                                <strong style="color: #fff;">${DOMPurify.sanitize(mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).mod_name.length > 20 ? mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).mod_name.substring(0, 20) + '...' : mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).mod_name)}</strong>
                                                <br>
                                                <span style="color: gray;">by ${DOMPurify.sanitize(mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).author.length > 20 ? mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).author.substring(0, 20) + '...' : mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).author)}</span>
                                                <br><br>
                                                <span style="color: #fff; white-space: normal; word-break: break-all;" class="is-size-7">${DOMPurify.sanitize(mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).short_description)}</span>
                                                <br><br>
                                                <span class="mod-tag">${DOMPurify.sanitize(mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).version)}</span>
                                                ${mods.find(mod => `modal-${mod.mod_id}` === button.dataset.target).tags.split(',').map(tag => `<span class="mod-tag" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${DOMPurify.sanitize(tag.replace(/"/g, ''))}</span>`).join('')}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        `;
                    });
                });

                const modalCloses = document.querySelectorAll('.modal-close');
                modalCloses.forEach(close => {
                    close.addEventListener('click', () => {
                        close.parentElement.classList.remove('is-active');
                    });
                });

                const paginationContainer = document.getElementById('mod-list-pagination');
                paginationContainer.innerHTML = '';

                if (page > 1) {
                    const prevButton = document.createElement('button');
                    prevButton.className = 'button is-primary';
                    prevButton.style.backgroundColor = '#4a4a4a';
                    prevButton.style.color = '#fff';
                    prevButton.textContent = 'Previous';
                    prevButton.addEventListener('click', () => {
                        loadMods(filterTag, page - 1, sortBy, versionSort, searchQuery);
                    });
                    paginationContainer.appendChild(prevButton);
                }

                const pageNumber = document.createElement('span');
                pageNumber.style.color = '#fff';
                pageNumber.style.margin = '0 10px';
                pageNumber.textContent = `Page ${page} of ${totalPages}`;
                paginationContainer.appendChild(pageNumber);

                if (page < totalPages) {
                    const nextButton = document.createElement('button');
                    nextButton.className = 'button is-primary';
                    nextButton.style.backgroundColor = '#4a4a4a';
                    nextButton.style.color = '#fff';
                    nextButton.textContent = 'Next';
                    nextButton.style.marginLeft = '20px';
                    nextButton.addEventListener('click', () => {
                        loadMods(filterTag, page + 1, sortBy, versionSort, searchQuery);
                    });
                    paginationContainer.appendChild(nextButton);
                }
            });
    };

    loadMods();

    fetch('/get-tags')
        .then(response => response.json())
        .then(tags => {
            const tagsSelect = document.getElementById('tags-select');
            if (tagsSelect) {
                const allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'All';
                tagsSelect.appendChild(allOption);

                tags.forEach(tag => {
                    const option = document.createElement('option');
                    option.value = tag.name;
                    option.textContent = DOMPurify.sanitize(tag.name);
                    tagsSelect.appendChild(option);
                });

                tagsSelect.addEventListener('change', (event) => {
                    const selectedTag = event.target.value;
                    loadMods(selectedTag !== 'all' ? selectedTag : null);
                });
            }
        });

    const categorySelect = document.getElementById('category-select');
    if (categorySelect) {
        categorySelect.addEventListener('change', (event) => {
            const selectedCategory = event.target.value;
            loadMods(null, 1, selectedCategory === 'popular' ? 'popular' : 'default');
        });
    }

    const versionSortSelect = document.getElementById('version-sort');
    if (versionSortSelect) {
        versionSortSelect.addEventListener('change', (event) => {
            const selectedVersionSort = event.target.value;
            loadMods(null, 1, 'default', selectedVersionSort);
        });
    }

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let typingTimer;
        const doneTypingInterval = 500;
        searchInput.addEventListener('input', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                const searchQuery = searchInput.value;
                if (searchQuery === '') {
                    loadMods();
                } else {
                    loadMods(null, 1, 'default', 'asc', searchQuery);
                }
            }, doneTypingInterval);
        });

        searchInput.addEventListener('keydown', () => {
            clearTimeout(typingTimer);
        });
    }
});
