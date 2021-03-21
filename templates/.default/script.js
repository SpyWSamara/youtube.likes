[...document.querySelectorAll('.youtube-search-wrapper')].forEach(youtubeSearchWidget => {
    const form = youtubeSearchWidget.querySelector('form');
    const q = form.querySelector('input[name=q]');
    const signedParameters = form.querySelector('input[name=signedParameters]');
    const statusLine = youtubeSearchWidget.querySelector('.status');
    const resultList = youtubeSearchWidget.querySelector('ul.result');
    const timeout = 750;
    const trottle = (func, timeout) => {
        let timeoutId;

        return _ => {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            timeoutId = setTimeout(func, timeout);
        };
    };
    const search = _ => {
        resultList.innerHTML = '';
        statusLine.innerHTML = '';
        BX.ajax.runComponentAction(
            'spywsamara:youtube.likes',
            'search',
            {
                mode: 'class',
                signedParameters: signedParameters ? signedParameters.value : '',
                data: new FormData(form)
            }
        ).then(showSearchResult).catch(console.error);
    };
    const add = id => {
        statusLine.innerHTML = '';
        BX.ajax.runComponentAction(
            'spywsamara:youtube.likes',
            'add',
            {
                mode: 'class',
                signedParameters: signedParameters ? signedParameters.value : '',
                data: {id: id}
            }
        ).then(addActionResult).catch(console.error);
    };
    const remove = id => {
        statusLine.innerHTML = '';
        BX.ajax.runComponentAction(
            'spywsamara:youtube.likes',
            'remove',
            {
                mode: 'class',
                signedParameters: signedParameters ? signedParameters.value : '',
                data: {id: id}
            }
        ).then(removeActionResult).catch(console.error);
    };
    const addActionResult = result => {
        if (result.data) {
            statusLine.innerHTML = 'Видео добавлено в избранные!';
            clearStatus();
        }
    }
    const removeActionResult = result => {
        if (result.data) {
            statusLine.innerHTML = 'Видео убрано из избранных!';
            clearStatus();
        }
    };
    const clearStatus = _ => {
        setTimeout(_ => {
            statusLine.innerHTML = '';
        }, 5 * 1000);
    };
    const showSearchResult = result => {
        const list = result.data;

        const html = list.map(item => {
            return `<li>
                <img src="https://img.youtube.com/vi/${item.id}/maxresdefault.jpg" alt="preview" loading="lazy" width="200">
                <p>
                    <input type="checkbox" name="video" value="${item.id}" ${item.checked ? 'checked' : ''}>
                    <a href="https://www.youtube.com/watch?v=${item.id}">${item.title}</a>
                </p>
                <small>${item.author}</small>
            </li>`;
        });
        resultList.innerHTML = html.join('');
    };

    q.addEventListener('input', trottle(search, timeout));
    resultList.addEventListener('change', event => {
        const target = event.target;
        if ('INPUT' === target.nodeName && 'checkbox' === target.type) {
            if (target.checked) {
                add(target.value);
            } else {
                remove(target.value);
            }
        }
    });
});
