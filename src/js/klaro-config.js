window.klaroConfig = {
    version: 1,
    elementID: 'klaro',
    storageMethod: 'cookie',
    cookieName: 'tswebsite_klaro',
    cookieExpiresAfterDays: 365,
    mustConsent: false,
    acceptAll: true,
    hideDeclineAll: false,
    lang: document.documentElement.lang ? document.documentElement.lang.split('-')[0] : 'de',
    translations: {
        zz: { privacyPolicyUrl: '#' },
        de: {
            consentNotice: {
                description: 'Diese Seite bettet externe Medien (YouTube, Vimeo) ein. Bitte stimme zu, damit eingebettete Inhalte geladen werden dürfen.'
            },
            consentModal: {
                title: 'Datenschutz-Einstellungen',
                description: 'Hier kannst du einstellen, welche externen Dienste du erlauben möchtest. Deine Einstellung wird in einem Cookie gespeichert.'
            },
            decline: 'Ablehnen',
            acceptAll: 'Alle akzeptieren',
            acceptSelected: 'Auswahl bestätigen',
            close: 'Schließen',
            save: 'Speichern',
            purposes: {
                media: 'Externe Medien'
            },
            youtube: {
                title: 'YouTube',
                description: 'YouTube-Videos werden von Google LLC gehostet. Mit deiner Zustimmung werden Inhalte von youtube-nocookie.com geladen. Google kann dabei Daten über dein Nutzungsverhalten erheben.'
            },
            vimeo: {
                title: 'Vimeo',
                description: 'Vimeo-Videos werden von Vimeo LLC gehostet. Mit deiner Zustimmung werden Inhalte von player.vimeo.com geladen. Vimeo kann dabei Daten über dein Nutzungsverhalten erheben.'
            }
        },
        en: {
            consentNotice: {
                description: 'This page embeds external media (YouTube, Vimeo). Please agree so embedded content can be loaded.'
            },
            consentModal: {
                title: 'Privacy Settings',
                description: 'Here you can choose which external services you want to allow. Your settings are saved in a cookie.'
            },
            decline: 'Decline',
            acceptAll: 'Accept all',
            acceptSelected: 'Save selection',
            close: 'Close',
            save: 'Save',
            purposes: {
                media: 'External Media'
            },
            youtube: {
                title: 'YouTube',
                description: 'YouTube videos are hosted by Google LLC. With your consent, content from youtube-nocookie.com will be loaded. Google may collect data about your usage.'
            },
            vimeo: {
                title: 'Vimeo',
                description: 'Vimeo videos are hosted by Vimeo LLC. With your consent, content from player.vimeo.com will be loaded. Vimeo may collect data about your usage.'
            }
        }
    },
    services: [
        {
            name: 'youtube',
            title: 'YouTube',
            purposes: ['media'],
            cookies: [
                [/^VISITOR_INFO/, '/', '.youtube.com'],
                [/^YSC/, '/', '.youtube.com'],
                [/^yt-remote/, '/', '.youtube.com']
            ]
        },
        {
            name: 'vimeo',
            title: 'Vimeo',
            purposes: ['media'],
            cookies: [
                [/^vuid/, '/', '.vimeo.com']
            ]
        }
    ]
};
