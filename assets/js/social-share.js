(function () {
    const eventId = window.APP_CONFIG.eventId || '';
    const pageUrl = window.location.href;
    const pageTitle = document.title;
    const pageDescription = document.querySelector('meta[name="description"]')?.content || 'Watch this live event on NFHS Network';
    const pageImage = document.querySelector('meta[property="og:image"]')?.content || '';

    // Event data for copy template
    const homeName = window.APP_CONFIG.homeName || 'Home Team';
    const awayName = window.APP_CONFIG.awayName || 'Away Team';
    const eventDetails = window.APP_CONFIG.description || '';
    const humanDate = window.APP_CONFIG.humanDate || 'TBA';
    const locationText = window.APP_CONFIG.locationText || '';

    // Parse date and time from humanDate
    function parseDateTime(dateStr) {
        if (!dateStr || dateStr === 'TBA') {
            return { date: 'TBA', time: 'TBA' };
        }

        let date = dateStr;
        let time = '';

        if (dateStr.includes(' | ')) {
            const parts = dateStr.split(' | ');
            date = parts[0] ? parts[0].trim() : dateStr;
            if (parts[1]) {
                time = parts[1].trim();
                time = time.replace(/\s+[A-Z]{2,4}$/, '');
            }
        } else if (dateStr.includes(' at ')) {
            const parts = dateStr.split(' at ');
            date = parts[0].trim();
            time = parts[1] ? parts[1].trim() : '';
        } else if (dateStr.includes('T')) {
            const parts = dateStr.split('T');
            date = parts[0];
            if (parts[1]) {
                const timePart = parts[1].split('.')[0];
                const [hours, minutes] = timePart.split(':');
                const hour24 = parseInt(hours, 10);
                const ampm = hour24 >= 12 ? 'PM' : 'AM';
                const hour12 = hour24 > 12 ? hour24 - 12 : (hour24 === 0 ? 12 : hour24);
                time = `${hour12}:${minutes} ${ampm}`;
            }
        } else {
            const timeMatch = dateStr.match(/(\d{1,2}:\d{2}\s*(?:AM|PM|am|pm))$/i);
            if (timeMatch) {
                time = timeMatch[1];
                date = dateStr.replace(timeMatch[0], '').trim();
            } else {
                time = 'TBA';
            }
        }

        return {
            date: date || dateStr,
            time: time || 'TBA'
        };
    }

    const { date: eventDate, time: eventTime } = parseDateTime(humanDate);

    const shareTemplateWithUrl = `${homeName} vs ${awayName} - ${eventDetails}

CH 1 ${pageUrl}

📅 ${eventDate}
🕐 ${eventTime}

📍 ${locationText || 'TBA'}`;

    const shareTemplateWithoutUrl = `${homeName} vs ${awayName} - ${eventDetails}

📅 ${eventDate}
🕐 ${eventTime}

📍 ${locationText || 'TBA'}`;

    const shareFacebook = document.getElementById('shareFacebook');
    const shareTwitter = document.getElementById('shareTwitter');
    const shareWhatsApp = document.getElementById('shareWhatsApp');
    const shareTelegram = document.getElementById('shareTelegram');
    const shareCopy = document.getElementById('shareCopy');

    if (shareFacebook) {
        shareFacebook.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(shareTemplateWithUrl);
                }
                shareFacebook.classList.add('copied');
                setTimeout(() => {
                    shareFacebook.classList.remove('copied');
                    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}`;
                    window.open(url, '_blank');
                }, 500);
            } catch (err) {
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}`, '_blank');
            }
        });
    }

    if (shareTwitter) {
        shareTwitter.addEventListener('click', (e) => {
            e.preventDefault();
            const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareTemplateWithUrl)}`;
            window.open(url, '_blank');
        });
    }

    if (shareWhatsApp) {
        shareWhatsApp.addEventListener('click', (e) => {
            e.preventDefault();
            const url = `https://wa.me/?text=${encodeURIComponent(shareTemplateWithUrl)}`;
            window.open(url, '_blank');
        });
    }

    if (shareTelegram) {
        shareTelegram.addEventListener('click', (e) => {
            e.preventDefault();
            const url = `https://t.me/share/url?url=${encodeURIComponent(pageUrl)}&text=${encodeURIComponent(shareTemplateWithoutUrl)}`;
            window.open(url, '_blank');
        });
    }

    if (shareCopy) {
        shareCopy.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(shareTemplateWithUrl);
                }
                shareCopy.classList.add('copied');
                setTimeout(() => {
                    shareCopy.classList.remove('copied');
                }, 2000);
            } catch (err) {
                alert('Failed to copy link. Please copy manually.');
            }
        });
    }
})();
