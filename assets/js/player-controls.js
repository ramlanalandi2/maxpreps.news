/**
 * Video Player Controls - YouTube Live Style
 * Extracted from index.php for performance optimization.
 */
(() => {
    const video = document.getElementById('mainVideoPlayer');
    const overlay = document.getElementById('videoOverlay');
    const playButton = document.getElementById('playButton');
    const countdownTimer = document.getElementById('countdownTimer');
    const countdownDays = document.getElementById('countdownDays');
    const countdownHours = document.getElementById('countdownHours');
    const countdownMinutes = document.getElementById('countdownMinutes');
    const countdownSeconds = document.getElementById('countdownSeconds');
    const playPauseBtn = document.getElementById('playPauseBtn');
    const muteBtn = document.getElementById('muteBtn');
    const volumeSlider = document.getElementById('volumeSlider');
    const progressBar = document.getElementById('progressBar');
    const progressFilled = document.getElementById('progressFilled');
    const progressBuffered = document.getElementById('progressBuffered');
    const progressHover = document.getElementById('progressHover');
    const currentTimeEl = document.getElementById('currentTime');
    const durationEl = document.getElementById('duration');
    const timeDisplay = document.getElementById('timeDisplay');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const videoControls = document.getElementById('videoControls');
    const videoPlayer = document.querySelector('.video-player');
    const ctaOverlay = document.getElementById('ctaOverlay');
    const ctaBackdrop = document.getElementById('ctaBackdrop');
    const ctaCloseBtn = document.getElementById('ctaCloseBtn');

    let controlsTimeout;
    let isDragging = false;
    let ctaShown = false;
    let isHidingOverlay = false;
    let countdownInterval = null;

    // Countdown Timer Functions
    function updateCountdown() {
        if (!overlay || !countdownTimer || !playButton) return;

        const eventDateStr = overlay.dataset.eventDate;
        if (!eventDateStr) {
            // No event date, show play button
            countdownTimer.style.display = 'none';
            playButton.style.display = 'flex';
            return;
        }

        try {
            const eventDate = new Date(eventDateStr);
            const now = new Date();
            const diff = eventDate.getTime() - now.getTime();

            if (diff > 0) {
                // Event hasn't started - show countdown
                countdownTimer.style.display = 'block';
                playButton.style.display = 'none';

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                if (countdownDays) countdownDays.textContent = String(days).padStart(2, '0');
                if (countdownHours) countdownHours.textContent = String(hours).padStart(2, '0');
                if (countdownMinutes) countdownMinutes.textContent = String(minutes).padStart(2, '0');
                if (countdownSeconds) countdownSeconds.textContent = String(seconds).padStart(2, '0');
            } else {
                // Event has started or passed - show play button
                countdownTimer.style.display = 'none';
                playButton.style.display = 'flex';
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
            }
        } catch (e) {
            console.log('Countdown error:', e);
            // On error, show play button
            countdownTimer.style.display = 'none';
            playButton.style.display = 'flex';
        }
    }

    // Initialize countdown on page load
    if (overlay && overlay.dataset.eventDate) {
        updateCountdown();
        // Update countdown every second
        countdownInterval = setInterval(updateCountdown, 1000);
    }

    // Set backdrop image from poster
    if (ctaBackdrop) {
        const posterUrl = videoPlayer?.dataset.poster || video?.poster || '';
        if (posterUrl) {
            ctaBackdrop.style.backgroundImage = `url('${posterUrl}')`;
        }
    }

    // Format time
    function formatTime(seconds) {
        if (isNaN(seconds) || seconds === 0) return '--:--';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Update time display
    function updateTime() {
        if (video.duration && video.duration > 0) {
            currentTimeEl.textContent = formatTime(video.currentTime);
            durationEl.textContent = formatTime(video.duration);
            timeDisplay.classList.add('loaded');
        } else {
            timeDisplay.classList.remove('loaded');
        }
    }

    // Update progress bar
    function updateProgress() {
        if (!video || !progressFilled) return;

        try {
            if (video.duration && video.duration > 0 && !isNaN(video.duration) && isFinite(video.duration)) {
                // Normal video with duration
                const percent = (video.currentTime / video.duration) * 100;
                progressFilled.style.width = Math.min(100, Math.max(0, percent)) + '%';
            } else {
                // For live stream or when duration is not available - fill to 100%
                progressFilled.style.width = '100%';
            }
        } catch (e) {
            console.log('Progress update error:', e);
        }
    }

    // Update buffered progress
    function updateBuffered() {
        if (video.buffered.length > 0 && video.duration) {
            const bufferedEnd = video.buffered.end(video.buffered.length - 1);
            const percent = (bufferedEnd / video.duration) * 100;
            progressBuffered.style.width = percent + '%';
        }
    }


    // Show CTA overlay
    function showCTAOverlay() {
        if (!ctaShown && ctaOverlay) {
            ctaShown = true;

            // Ensure video is paused
            if (!video.paused) {
                video.pause();
            }

            ctaOverlay.classList.add('active');

            // Always keep play-icon visible, pause-icon hidden
            const playIcon = playPauseBtn?.querySelector('.play-icon');
            const pauseIcon = playPauseBtn?.querySelector('.pause-icon');
            if (playIcon) {
                playIcon.style.display = 'block';
            }
            if (pauseIcon) {
                pauseIcon.style.display = 'none';
            }
        }
    }

    // Hide CTA overlay
    function hideCTAOverlay() {
        if (ctaOverlay) {
            isHidingOverlay = true;
            ctaOverlay.classList.remove('active');

            // Reset flag so overlay can show again
            ctaShown = false;

            // Show overlay play button
            overlay?.classList.remove('hidden');

            // Ensure video is paused
            if (!video.paused) {
                video.pause();
            }

            // Always keep play-icon visible, pause-icon hidden
            const playIcon = playPauseBtn?.querySelector('.play-icon');
            const pauseIcon = playPauseBtn?.querySelector('.pause-icon');
            if (playIcon) {
                playIcon.style.display = 'block';
            }
            if (pauseIcon) {
                pauseIcon.style.display = 'none';
            }

            setTimeout(() => {
                isHidingOverlay = false;
            }, 100);
        }
    }

    // Show/hide controls
    function showControls() {
        videoControls.classList.add('visible');
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(() => {
            if (!video.paused) {
                videoControls.classList.remove('visible');
            }
        }, 3000);
    }

    function hideControls() {
        if (!video.paused) {
            videoControls.classList.remove('visible');
        }
    }

    // Play/Pause
    function togglePlay() {
        // Don't allow play if countdown timer is showing
        if (countdownTimer && countdownTimer.style.display !== 'none') {
            return;
        }

        if (video.paused) {
            // Show CTA overlay when play button is clicked
            showCTAOverlay();
            overlay.classList.add('hidden');
        } else {
            video.pause();
            overlay.classList.remove('hidden');
            const playIcon = playPauseBtn.querySelector('.play-icon');
            const pauseIcon = playPauseBtn.querySelector('.pause-icon');
            if (playIcon) playIcon.style.display = 'block';
            if (pauseIcon) pauseIcon.style.display = 'none';
        }
    }

    // Mute/Unmute
    function toggleMute() {
        video.muted = !video.muted;
        volumeSlider.value = video.muted ? 0 : video.volume * 100;
        const highIcon = muteBtn.querySelector('.volume-high-icon');
        const mutedIcon = muteBtn.querySelector('.volume-muted-icon');
        if (video.muted || video.volume === 0) {
            if (highIcon) highIcon.style.display = 'none';
            if (mutedIcon) mutedIcon.style.display = 'block';
        } else {
            if (highIcon) highIcon.style.display = 'block';
            if (mutedIcon) mutedIcon.style.display = 'none';
        }
    }

    // Fullscreen
    function toggleFullscreen() {
        const fullIcon = fullscreenBtn?.querySelector('.fullscreen-icon');
        const exitIcon = fullscreenBtn?.querySelector('.fullscreen-exit-icon');

        if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
            // Enter fullscreen
            const requestFullscreen = videoPlayer.requestFullscreen ||
                videoPlayer.webkitRequestFullscreen ||
                videoPlayer.mozRequestFullScreen ||
                videoPlayer.msRequestFullscreen;

            if (requestFullscreen) {
                requestFullscreen.call(videoPlayer).catch(err => {
                    console.log('Error attempting to enable fullscreen:', err);
                });
            }

            if (fullIcon) fullIcon.style.display = 'none';
            if (exitIcon) exitIcon.style.display = 'block';
        } else {
            // Exit fullscreen
            const exitFullscreen = document.exitFullscreen ||
                document.webkitExitFullscreen ||
                document.mozCancelFullScreen ||
                document.msExitFullscreen;

            if (exitFullscreen) {
                exitFullscreen.call(document);
            }

            if (fullIcon) fullIcon.style.display = 'block';
            if (exitIcon) exitIcon.style.display = 'none';
        }
    }

    // Seek video
    function seek(e) {
        if (!video.duration) return;
        const rect = progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        video.currentTime = percent * video.duration;
    }

    // Event Listeners
    playButton?.addEventListener('click', (e) => {
        togglePlay();
    });

    playPauseBtn?.addEventListener('click', (e) => {
        togglePlay();
    });

    video?.addEventListener('click', () => {
        if (ctaOverlay && !ctaOverlay.classList.contains('active')) {
            togglePlay();
        }
    });

    ctaCloseBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        hideCTAOverlay();
    });

    ctaOverlay?.addEventListener('click', (e) => {
        if (e.target === ctaOverlay || e.target === ctaBackdrop) {
            hideCTAOverlay();
        }
    });

    ctaOverlay?.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    muteBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMute();
    });

    volumeSlider?.addEventListener('input', (e) => {
        video.volume = e.target.value / 100;
        video.muted = e.target.value === 0;
        const highIcon = muteBtn.querySelector('.volume-high-icon');
        const mutedIcon = muteBtn.querySelector('.volume-muted-icon');
        if (e.target.value === 0) {
            if (highIcon) highIcon.style.display = 'none';
            if (mutedIcon) mutedIcon.style.display = 'block';
        } else {
            if (highIcon) highIcon.style.display = 'block';
            if (mutedIcon) mutedIcon.style.display = 'none';
        }
    });

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            toggleFullscreen();
        });

        fullscreenBtn.addEventListener('touchend', (e) => {
            e.stopPropagation();
            e.preventDefault();
            toggleFullscreen();
        }, { passive: false });

        fullscreenBtn.addEventListener('touchstart', (e) => {
            e.stopPropagation();
        }, { passive: true });
    }

    progressBar?.addEventListener('click', (e) => {
        e.stopPropagation();
        seek(e);
    });

    progressBar?.addEventListener('mousemove', (e) => {
        if (!video.duration) return;
        const rect = progressBar.getBoundingClientRect();
        const percent = ((e.clientX - rect.left) / rect.width) * 100;
        progressHover.style.width = percent + '%';
    });

    progressBar?.addEventListener('mouseleave', () => {
        progressHover.style.width = '0%';
    });

    video?.addEventListener('timeupdate', () => {
        updateProgress();
        updateTime();
        updateBuffered();
    });

    video?.addEventListener('loadedmetadata', () => {
        updateTime();
        updateProgress();
        updateBuffered();
    });

    video?.addEventListener('progress', () => {
        updateBuffered();
    });

    video?.addEventListener('play', () => {
        showControls();
        updateProgress();
    });

    video?.addEventListener('pause', () => {
        showControls();
    });

    videoPlayer?.addEventListener('mousemove', () => {
        showControls();
    });

    videoPlayer?.addEventListener('mouseleave', () => {
        hideControls();
    });

    const fullscreenChangeEvents = ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'];
    fullscreenChangeEvents.forEach(eventName => {
        document.addEventListener(eventName, () => {
            const fullIcon = fullscreenBtn?.querySelector('.fullscreen-icon');
            const exitIcon = fullscreenBtn?.querySelector('.fullscreen-exit-icon');
            const isFullscreen = document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.mozFullScreenElement ||
                document.msFullscreenElement;

            if (isFullscreen) {
                if (fullIcon) fullIcon.style.display = 'none';
                if (exitIcon) exitIcon.style.display = 'block';
            } else {
                if (fullIcon) fullIcon.style.display = 'block';
                if (exitIcon) exitIcon.style.display = 'none';
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (document.activeElement.tagName === 'INPUT') return;

        switch (e.key) {
            case ' ':
            case 'k':
                e.preventDefault();
                togglePlay();
                break;
            case 'm':
                e.preventDefault();
                toggleMute();
                break;
            case 'f':
                e.preventDefault();
                toggleFullscreen();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                video.currentTime = Math.max(0, video.currentTime - 10);
                break;
            case 'ArrowRight':
                e.preventDefault();
                video.currentTime = Math.min(video.duration, video.currentTime + 10);
                break;
        }
    });

    if (progressFilled) {
        if (!video || !video.duration || video.duration === 0 || isNaN(video.duration) || !isFinite(video.duration)) {
            progressFilled.style.width = '100%';
        } else {
            updateProgress();
        }
    }
})();
