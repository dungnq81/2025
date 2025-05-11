// utils/cookie.js

/**
 * CookieService: Provides methods to get, set, and delete browser cookies.
 * Uses the native Cookie Store API if available, with fallback to document.cookie.
 */
export default class CookieService {
    /**
     * Get the value of a cookie by name.
     *
     * @param name
     * @returns {Promise<string|string|*>}
     */
    static async get(name) {
        if (window.cookieStore) {
            const entry = await window.cookieStore.get(name);
            return entry?.value || '';
        }

        // Fallback document.cookie
        const pattern = new RegExp(
            `(^|;)\\s*${encodeURIComponent(name)}\\s*=\\s*([^;]+)`
        );
        const match = document.cookie.match(pattern);
        return match ? decodeURIComponent(match[2]) : '';
    }

    /**
     * Set a cookie with the specified name and value.
     *
     * @param name
     * @param value
     * @param days
     * @param path
     * @param secure
     * @param sameSite
     * @returns {Promise<*>}
     */
    static async set(name, value, {days = 365, path = '/', secure = true, sameSite = 'Lax'} = {}) {
        if (window.cookieStore) {
            const opts = { name, value, path, sameSite };
            if (days) {
                const expires = new Date(Date.now() + days * 864e5);
                opts.expires = expires;
            }
            if (secure) opts.secure = true;
            return window.cookieStore.set(opts);
        }

        // Fallback document.cookie
        let cookieStr = `${encodeURIComponent(name)}=${encodeURIComponent(value)};path=${path};SameSite=${sameSite}`;
        if (days) {
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            cookieStr += `;expires=${expires}`;
        }
        if (secure) {
            cookieStr += ';secure';
        }
        document.cookie = cookieStr;
    }

    /**
     * Delete a cookie by name.
     *
     * @param name
     * @param path
     * @param sameSite
     * @returns {Promise<*>}
     */
    static async delete(name, {path = '/', sameSite = 'Lax'} = {}) {
        if (window.cookieStore) {
            return window.cookieStore.delete(name, { path, sameSite });
        }

        // Set an expiration date in the past to remove the cookie
        document.cookie = `${encodeURIComponent(name)}=;path=${path};expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=${sameSite}`;
    }
}
