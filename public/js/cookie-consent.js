(function () {
    const CONSENT_COOKIE = 'sdmd_cookie_consent';
    const CONSENT_TTL_DAYS = 180;
    const DECLINE_TTL_DAYS = 30;

    const modalElement = document.getElementById('cookieConsentModal');
    if (!modalElement) {
        return;
    }

    const modal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });

    const acceptBtn = modalElement.querySelector('[data-action="cookie-accept"]');
    const declineBtn = modalElement.querySelector('[data-action="cookie-decline"]');

    const setCookie = (name, value, days) => {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
    };

    const getCookie = (name) => {
        const matches = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1')}=([^;]*)`));
        return matches ? decodeURIComponent(matches[1]) : null;
    };

    const hasConsent = () => {
        const value = getCookie(CONSENT_COOKIE);
        return value === 'accepted' || value === 'declined';
    };

    const dispatchConsentEvent = (status) => {
        window.dispatchEvent(new CustomEvent('cookie-consent-change', {
            detail: { status }
        }));
    };

    const handleConsent = (status) => {
        const ttl = status === 'accepted' ? CONSENT_TTL_DAYS : DECLINE_TTL_DAYS;
        setCookie(CONSENT_COOKIE, status, ttl);
        modal.hide();
        dispatchConsentEvent(status);
    };

    if (!hasConsent()) {
        modal.show();
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', () => handleConsent('accepted'));
    }

    if (declineBtn) {
        declineBtn.addEventListener('click', () => handleConsent('declined'));
    }
})();
