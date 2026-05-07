(() => {
    const root = document.querySelector('.trade-chat');
    if (!(root instanceof HTMLElement)) return;

    const orderId = root.dataset.orderId || '';
    const userId = root.dataset.userId || '';

    const debounce = (fn, waitMs) => {
        let timerId = null;
        return () => {
            if (timerId) window.clearTimeout(timerId);
            timerId = window.setTimeout(fn, waitMs);
        };
    };

    const safeLocalStorageGet = (key) => {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    };

    const safeLocalStorageSet = (key, value) => {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // ignore
        }
    };

    const safeLocalStorageRemove = (key) => {
        try {
            window.localStorage.removeItem(key);
        } catch {
            // ignore
        }
    };

    const initScrollToBottom = () => {
        const messages = document.getElementById('tradeChatMessages');
        if (!messages) return;
        messages.scrollTop = messages.scrollHeight;
    };

    const initRatingStars = () => {
        const starsUi = document.getElementById('ratingStarsUi');
        const starsInput = document.getElementById('ratingStars');
        if (!starsUi || !(starsInput instanceof HTMLInputElement)) return;

        const setStars = (value) => {
            starsInput.value = String(value);
            starsUi.querySelectorAll('.trade-chat__star').forEach((el) => {
                const v = Number(el.getAttribute('data-value'));
                el.classList.toggle('is-on', v <= value);
            });
        };

        const initial = Number(starsUi.getAttribute('data-initial')) || 0;
        setStars(initial);

        starsUi.addEventListener('click', (e) => {
            const target = e.target;
            if (!(target instanceof HTMLElement)) return;
            const v = Number(target.getAttribute('data-value'));
            if (!v) return;
            setStars(v);
        });
    };

    const initDraftMessage = () => {
        const sendForm = document.querySelector('form.trade-chat__send');
        const messageInput = sendForm ? sendForm.querySelector('input[name="message"]') : null;
        if (!(messageInput instanceof HTMLInputElement)) return;

        const draftMessageKey = `tradeChatDraftMessage:${userId}:${orderId}`;

        const currentValue = messageInput.value || '';
        if (!currentValue) {
            const draftValue = safeLocalStorageGet(draftMessageKey) || '';
            if (draftValue) {
                messageInput.value = draftValue;
            }
        }

        const saveDraft = debounce(() => {
            safeLocalStorageSet(draftMessageKey, messageInput.value || '');
        }, 150);
        messageInput.addEventListener('input', saveDraft);

        if (sendForm) {
            sendForm.addEventListener('submit', () => {
                safeLocalStorageRemove(draftMessageKey);
            });
        }
    };

    const initAttachmentPreview = () => {
        const imageInput = document.getElementById('tradeChatImage');
        const attachment = document.getElementById('tradeChatAttachment');
        const attachmentThumb = document.getElementById('tradeChatAttachmentThumb');
        const attachmentName = document.getElementById('tradeChatAttachmentName');
        const attachmentRemove = document.getElementById('tradeChatAttachmentRemove');

        let objectUrl = null;
        const clearPreview = () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
            if (attachmentThumb) attachmentThumb.removeAttribute('src');
            if (attachmentName) attachmentName.textContent = '';
            if (attachment) attachment.classList.remove('is-visible');
        };

        const setPreview = (file) => {
            clearPreview();
            if (!file) return;
            if (attachmentName) attachmentName.textContent = file.name;
            if (attachmentThumb instanceof HTMLImageElement && file.type && file.type.startsWith('image/')) {
                objectUrl = URL.createObjectURL(file);
                attachmentThumb.src = objectUrl;
            }
            if (attachment) attachment.classList.add('is-visible');
        };

        if (imageInput instanceof HTMLInputElement) {
            imageInput.addEventListener('change', () => {
                const file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
                setPreview(file);
            });
        }

        if (attachmentRemove && imageInput instanceof HTMLInputElement) {
            attachmentRemove.addEventListener('click', () => {
                imageInput.value = '';
                clearPreview();
            });
        }

        window.addEventListener('beforeunload', () => {
            clearPreview();
        });
    };

    const setImageModalOpen = (src) => {
        const imageModal = document.getElementById('chatImageModal');
        const imageModalImg = document.getElementById('chatImageModalImg');
        const imageModalOpen = document.getElementById('chatImageModalOpen');
        if (
            !src ||
            !imageModal ||
            !(imageModalImg instanceof HTMLImageElement) ||
            !(imageModalOpen instanceof HTMLAnchorElement)
        ) {
            return;
        }

        imageModalImg.src = src;
        imageModalOpen.href = src;
        imageModal.classList.add('is-open');
        imageModal.setAttribute('aria-hidden', 'false');
    };

    const setImageModalClose = () => {
        const imageModal = document.getElementById('chatImageModal');
        const imageModalImg = document.getElementById('chatImageModalImg');
        if (imageModal) {
            imageModal.classList.remove('is-open');
            imageModal.setAttribute('aria-hidden', 'true');
        }
        if (imageModalImg instanceof HTMLImageElement) {
            imageModalImg.removeAttribute('src');
        }
    };

    const closeAllEdits = () => {
        document.querySelectorAll('.trade-chat__message.is-editing').forEach((el) => {
            el.classList.remove('is-editing');
            const textarea = el.querySelector('.trade-chat__inline-textarea');
            if (textarea instanceof HTMLTextAreaElement) {
                const original = textarea.getAttribute('data-original') || '';
                textarea.value = original;
            }
        });
    };

    const initClickHandlers = () => {
        document.addEventListener('click', (e) => {
            const target = e.target;
            if (!(target instanceof HTMLElement)) return;

            const openBtn = target.closest('.js-chat-image-open');
            if (openBtn instanceof HTMLElement) {
                const src = openBtn.getAttribute('data-src');
                setImageModalOpen(src);
                return;
            }

            if (target.classList.contains('js-chat-image-close')) {
                setImageModalClose();
                return;
            }

            if (target.classList.contains('js-chat-edit-open')) {
                const messageEl = target.closest('.trade-chat__message');
                if (!messageEl) return;
                closeAllEdits();
                messageEl.classList.add('is-editing');
                const textarea = messageEl.querySelector('.trade-chat__inline-textarea');
                if (textarea instanceof HTMLTextAreaElement) {
                    textarea.focus();
                    textarea.selectionStart = textarea.value.length;
                    textarea.selectionEnd = textarea.value.length;
                }
                return;
            }

            if (target.classList.contains('js-chat-edit-cancel')) {
                const messageEl = target.closest('.trade-chat__message');
                if (!messageEl) return;
                messageEl.classList.remove('is-editing');
                const textarea = messageEl.querySelector('.trade-chat__inline-textarea');
                if (textarea instanceof HTMLTextAreaElement) {
                    const original = textarea.getAttribute('data-original') || '';
                    textarea.value = original;
                }
            }
        });
    };

    const initInitialInlineEditFocus = () => {
        const initialEditingTextarea = document.querySelector(
            '.trade-chat__message.is-editing .trade-chat__inline-textarea'
        );
        if (!(initialEditingTextarea instanceof HTMLTextAreaElement)) return;
        initialEditingTextarea.focus();
        initialEditingTextarea.selectionStart = initialEditingTextarea.value.length;
        initialEditingTextarea.selectionEnd = initialEditingTextarea.value.length;
    };

    const initEscapeKey = () => {
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const imageModal = document.getElementById('chatImageModal');
            if (imageModal && imageModal.classList.contains('is-open')) {
                setImageModalClose();
            }
        });
    };

    initScrollToBottom();
    initRatingStars();
    initDraftMessage();
    initAttachmentPreview();
    initClickHandlers();
    initInitialInlineEditFocus();
    initEscapeKey();
})();
