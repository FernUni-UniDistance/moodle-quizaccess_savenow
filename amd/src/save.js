// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * "Save my answers" button on the quiz attempt page.
 *
 * It reuses the normal quiz navigation: the attempt form is submitted asking to come back
 * to the same page. Moodle then saves the answers exactly as when "Next page" is clicked,
 * without finishing the attempt.
 *
 * @module     quizaccess_savenow/save
 * @copyright  2026 UniDistance Suisse
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString, get_strings as getStrings} from 'core/str';
import {add as addToast} from 'core/toast';

/** @type {string} sessionStorage key remembering that a save was requested. */
const STORAGE_KEY = 'quizaccess_savenow_saved';

/**
 * Identify the attempt page, so the confirmation only shows after a save of that page.
 *
 * @param {HTMLFormElement} form The attempt form.
 * @returns {string|null}
 */
const getPageKey = (form) => {
    const attempt = form.querySelector('input[name="attempt"]');
    const thispage = form.querySelector('input[name="thispage"]');
    return attempt && thispage ? `${attempt.value}:${thispage.value}` : null;
};

/**
 * Submit the attempt form so that Moodle saves the answers and reloads the same page.
 *
 * @param {HTMLFormElement} form The attempt form.
 * @param {HTMLButtonElement} button The save button.
 */
const save = (form, button) => {
    const thispage = form.querySelector('input[name="thispage"]');
    const nextpage = form.querySelector('input[name="nextpage"]');
    if (!thispage || !nextpage || form.dataset.quizaccessSavenowSaving) {
        return;
    }
    form.dataset.quizaccessSavenowSaving = '1';
    nextpage.value = thispage.value;

    if (window.tinyMCE && window.tinyMCE.triggerSave) {
        window.tinyMCE.triggerSave();
    }
    try {
        window.sessionStorage.setItem(STORAGE_KEY, getPageKey(form));
    } catch (e) {
        // Storage unavailable: no confirmation message, the answers are saved anyway.
    }

    button.disabled = true;
    getString('saving', 'quizaccess_savenow')
        .then((text) => {
            button.textContent = text;
            return text;
        })
        .catch(() => null);

    if (form.requestSubmit) {
        form.requestSubmit();
    } else {
        form.submit();
    }
};

/**
 * Tell whether this page load follows a save of the same page, and forget it.
 *
 * @param {HTMLFormElement} form The attempt form.
 * @returns {boolean}
 */
const consumeSavedFlag = (form) => {
    try {
        const saved = window.sessionStorage.getItem(STORAGE_KEY);
        window.sessionStorage.removeItem(STORAGE_KEY);
        return saved !== null && saved === getPageKey(form);
    } catch (e) {
        return false;
    }
};

/**
 * Add the button and the keyboard shortcut to the attempt page.
 */
export const init = () => {
    const form = document.getElementById('responseform');
    if (!form) {
        return;
    }
    const justSaved = consumeSavedFlag(form);

    getStrings([
        {key: 'savenow', component: 'quizaccess_savenow'},
        {key: 'saved', component: 'quizaccess_savenow'},
        {key: 'shortcut', component: 'quizaccess_savenow'},
    ])
        .then(([saveNowText, savedText, shortcutText]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-secondary quizaccess-savenow';
            button.textContent = saveNowText;
            button.title = `${saveNowText} (${shortcutText})`;
            button.setAttribute('aria-keyshortcuts', 'Control+S Meta+S');
            button.addEventListener('click', () => save(form, button));

            const container = form.querySelector('.submitbtns');
            if (container) {
                container.insertBefore(button, container.firstChild);
            } else {
                form.appendChild(button);
            }

            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && !e.altKey && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    if (!e.repeat) {
                        save(form, button);
                    }
                }
            });

            if (justSaved) {
                addToast(savedText);
            }
            return null;
        })
        .catch(() => null);
};
