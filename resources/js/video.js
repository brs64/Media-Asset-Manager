import Hls from 'hls.js';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';

// Fonction pour initialiser le lecteur vidéo avec HLS.js et Plyr
const initLectureVideo = function() {
    const video = document.getElementById('player');

    if (!video) {
        console.error('Élément vidéo non trouvé');
        return;
    }

    const videoSrc = video.querySelector('source')?.src;

    if (!videoSrc) {
        console.error('Source vidéo non trouvée');
        return;
    }

    // Vérifier si c'est un stream HLS (m3u8) ou MP4 standard
    if (videoSrc.includes('.m3u8')) {
        // Utiliser HLS.js pour les streams HLS
        if (Hls.isSupported()) {
            const hls = new Hls({
                debug: false,
                enableWorker: true,
                lowLatencyMode: true,
                backBufferLength: 90
            });

            hls.loadSource(videoSrc);
            hls.attachMedia(video);

            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                // Initialiser Plyr après que HLS soit prêt
                initPlyrPlayer(video);
            });

            hls.on(Hls.Events.ERROR, function(event, data) {
                if (data.fatal) {
                    switch(data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            console.error('Erreur réseau fatale, tentative de récupération...');
                            hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            console.error('Erreur média fatale, tentative de récupération...');
                            hls.recoverMediaError();
                            break;
                        default:
                            console.error('Erreur fatale non récupérable');
                            hls.destroy();
                            break;
                    }
                }
            });

            // Stocker l'instance HLS pour un nettoyage éventuel
            window.hlsInstance = hls;

        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            // Safari natif supporte HLS
            video.src = videoSrc;
            initPlyrPlayer(video);
        } else {
            console.error('HLS n\'est pas supporté dans ce navigateur');
        }
    } else {
        // Pour les vidéos MP4 standard, initialiser directement Plyr
        initPlyrPlayer(video);
    }
};

// Fonction pour initialiser Plyr
function initPlyrPlayer(videoElement) {
    // Get media ID from the video page for preview thumbnails
    const mediaId = document.querySelector('[data-media-id]')?.dataset.mediaId;

    const player = new Plyr(videoElement, {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'captions',
                'settings',
                'pip',
                'airplay',
                'fullscreen'
            ],
            settings: ['quality', 'speed'],
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
            keyboard: { focused: true, global: false },
            tooltips: { controls: true, seek: true },
            previewThumbnails: mediaId ? {
                enabled: true,
                src: `/api/media/${mediaId}/preview-thumbnails.vtt`
            } : { enabled: false },
            captions: { active: false, language: 'auto' },
            fullscreen: {
                enabled: true,
                fallback: true,
                iosNative: false
            },
            storage: { enabled: true, key: 'plyr' },
            ratio: '16:9',
            autoplay: false,
            loop: { active: false },
            seekTime: 10,
            volume: 1,
            muted: false,
            clickToPlay: true,
            hideControls: true,
            resetOnEnd: false,
            disableContextMenu: false,
            loadSprite: true,
            iconPrefix: 'plyr',
            quality: {
                default: 720,
                options: [1080, 720, 480, 360],
                forced: false,
                onChange: (quality) => {
                    console.log(`Qualité changée à ${quality}p`);
                }
            }
        });

        // Événements du lecteur
        player.on('ready', event => {
            console.log('Lecteur vidéo prêt');
        });

        player.on('play', event => {
            console.log('Lecture démarrée');
        });

        player.on('pause', event => {
            console.log('Lecture mise en pause');
        });

        player.on('error', event => {
            console.error('Erreur du lecteur:', event);
        });

        // Stocker l'instance du player
        window.plyrPlayer = player;
}

// Fonction pour les fonctionnalités spécifiques à la page vidéo
const pageLectureVideo = function() {
    // Gestion du menu arborescence
    const btnArbo = document.getElementById('btnArborescence');
    const menuArbo = document.querySelector('.menuArbo');

    if (btnArbo && menuArbo) {
        btnArbo.addEventListener('click', function() {
            menuArbo.classList.toggle('active');
        });

        // Fermer le menu si on clique en dehors
        document.addEventListener('click', function(event) {
            if (!menuArbo.contains(event.target) && !btnArbo.contains(event.target)) {
                menuArbo.classList.remove('active');
            }
        });
    }

    // Gestion des boutons de partage
    const shareButtons = document.querySelectorAll('.share-button');
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            const url = window.location.href;
            const title = document.querySelector('.titre')?.textContent || 'Vidéo';

            if (this.classList.contains('share-facebook')) {
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
            } else if (this.classList.contains('share-twitter')) {
                window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
            } else if (this.classList.contains('share-link')) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Lien copié dans le presse-papiers !');
                }).catch(err => {
                    console.error('Erreur lors de la copie:', err);
                });
            }
        });
    });

    // Gestion du redimensionnement automatique de la description
    const description = document.querySelector('.description');
    if (description) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'toggle-description';
        toggleBtn.textContent = 'Voir plus';

        if (description.scrollHeight > description.clientHeight) {
            description.parentNode.appendChild(toggleBtn);

            toggleBtn.addEventListener('click', function() {
                description.classList.toggle('expanded');
                this.textContent = description.classList.contains('expanded') ? 'Voir moins' : 'Voir plus';
            });
        }
    }

    // Raccourcis clavier personnalisés
    document.addEventListener('keydown', function(e) {
        if (window.plyrPlayer) {
            switch(e.key) {
                case 'f':
                case 'F':
                    if (!e.ctrlKey && !e.metaKey) {
                        e.preventDefault();
                        window.plyrPlayer.fullscreen.toggle();
                    }
                    break;
                case 'm':
                case 'M':
                    e.preventDefault();
                    window.plyrPlayer.muted = !window.plyrPlayer.muted;
                    break;
                case 'ArrowLeft':
                    if (!e.shiftKey) {
                        e.preventDefault();
                        window.plyrPlayer.currentTime -= 10;
                    }
                    break;
                case 'ArrowRight':
                    if (!e.shiftKey) {
                        e.preventDefault();
                        window.plyrPlayer.currentTime += 10;
                    }
                    break;
                case ' ':
                    e.preventDefault();
                    window.plyrPlayer.togglePlay();
                    break;
            }
        }
    });

    // Nettoyage lors du déchargement de la page
    window.addEventListener('beforeunload', function() {
        if (window.hlsInstance) {
            window.hlsInstance.destroy();
        }
        if (window.plyrPlayer) {
            window.plyrPlayer.destroy();
        }
    });

    console.log('Page lecture vidéo initialisée');
};

// Rendre les fonctions disponibles globalement
window.initLectureVideo = initLectureVideo;
window.pageLectureVideo = pageLectureVideo;

// Export pour utilisation avec Vite si nécessaire
export { initLectureVideo, pageLectureVideo };