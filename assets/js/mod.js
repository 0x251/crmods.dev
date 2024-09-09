document.addEventListener('DOMContentLoaded', function () {
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
    const GetCurrentMod = window.location.pathname.split('/').pop();
    fetch(`/api/mod/${GetCurrentMod}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const mod = data.mod;
                document.getElementById('mod-icon').src = DOMPurify.sanitize(mod.mod_logo);
                document.getElementById('mod-name').innerText = DOMPurify.sanitize(mod.mod_name);
                document.getElementById('mod-author').innerText = 'by ' + DOMPurify.sanitize(mod.author);
                document.getElementById('mod-downloads').innerText = DOMPurify.sanitize(mod.download_count);
                document.getElementById('mod-id').value = DOMPurify.sanitize(mod.mod_id);
                document.getElementById('mod-version').value = DOMPurify.sanitize(mod.version);
                document.title = `CRMODS.DEV | ${DOMPurify.sanitize(mod.mod_name)} | ${DOMPurify.sanitize(mod.version)}`;
                if (window.innerWidth <= 768) {
                    const virustotalLink = document.getElementById('virustotal-link');
                    virustotalLink.textContent = 'VirusTotal Link';
                    virustotalLink.href = 'https://www.virustotal.com/gui/file/'+DOMPurify.sanitize(mod.file_id)+'/detection';
                } else {
                    const virustotalLink = document.getElementById('virustotal-link');
                    virustotalLink.textContent = 'https://www.virustotal.com/gui/file/'+DOMPurify.sanitize(mod.file_id)+'/detection';
                    virustotalLink.href = 'https://www.virustotal.com/gui/file/'+DOMPurify.sanitize(mod.file_id)+'/detection';
                }
                document.getElementById('mod-md5').value = DOMPurify.sanitize(mod.md5);
                document.getElementById('mod-sha256').value = DOMPurify.sanitize(mod.sha256);
                document.getElementById('mod-sha1').value = DOMPurify.sanitize(mod.sha1);
               
                const sizeInBytes = mod.size;
                let sizeWithUnit;
                if (sizeInBytes >= 1000000) {
                    sizeWithUnit = (sizeInBytes / 1000000).toFixed(2) + ' MB';
                } else {
                    sizeWithUnit = (sizeInBytes / 1000).toFixed(2) + ' KB';
                }
                document.getElementById('mod-size').value = DOMPurify.sanitize(sizeWithUnit);
                const descriptionElement = document.getElementById('mod-description');
                const sanitizedDescription = DOMPurify.sanitize(mod.long_description.replace(/\\n/g, '\n')
                    .replace(/https?:\/\/(?!.*(youtube|imgur)\.com)[^ ]+/gi, '[Unsafe link detected and removed]'));
                
                if (window.innerWidth <= 768) {
                    descriptionElement.style.wordBreak = 'break-word';
                }
                descriptionElement.innerHTML = `<md-block style="word-break: break-word;">${sanitizedDescription}</md-block>`;
                const modTagsContainer = document.getElementById('mod-tags');
                modTagsContainer.innerHTML = '';
                const modTags = DOMPurify.sanitize(mod.tags.replace(/"/g, '')).split(',').map(tag => tag.trim());
                
                modTags.forEach(tag => {
                    const tagSpan = document.createElement('span');
                    tagSpan.className = 'tag is-dark';
                    tagSpan.style.backgroundColor = '#4a4a4a';
                    tagSpan.style.color = '#fff';
                    tagSpan.style.margin = '5px';
                    tagSpan.innerText = DOMPurify.sanitize(tag);
                    modTagsContainer.appendChild(tagSpan);
                });

                const reportIcon = document.getElementById('report-icon');
                reportIcon.addEventListener('click', () => {
                    const reportModal = document.getElementById('report-modal');
                    reportModal.classList.add('is-active');
                });

                const reportModalClose = document.querySelector('#report-modal-close');
                reportModalClose.addEventListener('click', () => {
                    const reportModal = document.getElementById('report-modal');
                    reportModal.classList.remove('is-active');
                });

                const reportModalBackground = document.querySelector('#report-modal .modal-background');
                reportModalBackground.addEventListener('click', () => {
                    const reportModal = document.getElementById('report-modal');
                    reportModal.classList.remove('is-active');
                });

                const reportForm = document.getElementById('report-form');
                reportForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const reportReason = document.getElementById('report-reason').value;
                    fetch('/api/report-mod', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            modId: mod.mod_id,
                            reason: reportReason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Report Submitted',
                                text: 'Your report has been submitted successfully.',
                                background: '#333',
                                color: '#fff'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'There was an error submitting your report. Please try again later.',
                                background: '#333',
                                color: '#fff'
                            });
                        }
                        const reportModal = document.getElementById('report-modal');
                        reportModal.classList.remove('is-active');
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'There was an error submitting your report. Please try again later.',
                            background: '#333',
                            color: '#fff'
                        });
                        const reportModal = document.getElementById('report-modal');
                        reportModal.classList.remove('is-active');
                    });
                });
            }
        });
        
    const downloadButton = document.getElementById('download-button');
    downloadButton.addEventListener('click', () => {
        const modId = document.getElementById('mod-id').value;
        const modVersion = document.getElementById('mod-version').value;
        const modName = document.getElementById('mod-name').innerText;
        fetch(`/api/mod/${modId}/download`)
            .then(response => {
                if (response.headers.get('content-type').includes('application/json')) {
                    return response.json().then(data => {
                        if (!data.success) {
                            console.error('Error: Download not successful');
                        }
                    });
                } else {
                    return response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = `${modName}-${modVersion}.jar`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                    });
                }
            })
            .catch(error => console.error('Error downloading the mod:', error));
    });
});