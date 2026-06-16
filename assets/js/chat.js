// Fake Live Chat
(function () {
    const chatMessages = document.getElementById('chatMessages');
    const chatViewerCount = document.getElementById('chatViewerCount');
    if (!chatMessages) return;

    // Get team names from window.APP_CONFIG
    const homeTeamName = window.APP_CONFIG.homeName || "Home Team";
    const awayTeamName = window.APP_CONFIG.awayName || "Away Team";

    // Get event date and time from window.APP_CONFIG
    const humanDate = window.APP_CONFIG.humanDate || 'TBA';
    const locationText = window.APP_CONFIG.locationText || '';

    // Parse date and time from humanDate (format: "Monday, January 15, 2024 at 7:00 PM")
    function parseEventDateTime(dateTimeStr) {
        if (!dateTimeStr || dateTimeStr === 'TBA') {
            return { date: null, time: null, fullDate: null };
        }
        try {
            // Try to extract date and time
            const parts = dateTimeStr.split(' at ');
            const datePart = parts[0] || '';
            const timePart = parts[1] || '';
            return {
                date: datePart.trim(),
                time: timePart.trim(),
                fullDate: dateTimeStr.trim()
            };
        } catch (e) {
            return { date: dateTimeStr, time: '', fullDate: dateTimeStr };
        }
    }

    const eventDateTime = parseEventDateTime(humanDate);

    // Check if event has started (countdown timer visible means not started)
    function checkEventStatus() {
        const countdownTimer = document.getElementById('countdownTimer');
        const playButton = document.getElementById('playButton');
        // If countdown is visible, event hasn't started
        return !countdownTimer || (countdownTimer.style.display === 'none' && playButton && playButton.style.display !== 'none');
    }

    // Check if Q&A should be shown (countdown OR play button visible)
    function shouldShowQA() {
        const countdownTimer = document.getElementById('countdownTimer');
        const playButton = document.getElementById('playButton');
        const playButtonElement = document.querySelector('.video-player__play-button');

        // Show Q&A if:
        // 1. Countdown timer is visible (upcoming event)
        // 2. OR play button is visible (on-demand/replay)
        const countdownVisible = countdownTimer &&
            countdownTimer.style.display !== 'none' &&
            window.getComputedStyle(countdownTimer).display !== 'none';

        // Check play button visibility
        let playButtonVisible = false;
        if (playButton) {
            const playButtonStyle = window.getComputedStyle(playButton);
            playButtonVisible = playButtonStyle.display !== 'none' && playButtonStyle.visibility !== 'hidden';
        }
        if (!playButtonVisible && playButtonElement) {
            const playButtonElementStyle = window.getComputedStyle(playButtonElement);
            playButtonVisible = playButtonElementStyle.display !== 'none' && playButtonElementStyle.visibility !== 'hidden';
        }

        return countdownVisible || playButtonVisible;
    }

    // Check status initially and periodically
    let isEventStarted = checkEventStatus();
    setInterval(() => {
        isEventStarted = checkEventStatus();
    }, 2000); // Check every 2 seconds

    // Realistic names - Expanded list to avoid bot-like appearance
    const realisticNames = [
        'Mike', 'Sarah', 'James', 'Emily', 'David', 'Jessica', 'Chris', 'Amanda',
        'Ryan', 'Lisa', 'Michael', 'Jennifer', 'Daniel', 'Michelle', 'Andrew', 'Nicole',
        'Kevin', 'Ashley', 'Brian', 'Stephanie', 'Joshua', 'Melissa', 'Matthew', 'Lauren',
        'Jason', 'Rachel', 'Justin', 'Rebecca', 'Brandon', 'Samantha', 'Tyler', 'Amber',
        'Eric', 'Brittany', 'Jonathan', 'Courtney', 'Steven', 'Danielle', 'Thomas', 'Megan',
        'Robert', 'Katherine', 'Anthony', 'Christina', 'Mark', 'Stephanie', 'Paul', 'Elizabeth',
        'Alex', 'Olivia', 'Noah', 'Sophia', 'Liam', 'Emma', 'William', 'Ava',
        'Mason', 'Isabella', 'Ethan', 'Mia', 'James', 'Charlotte', 'Benjamin', 'Amelia',
        'Lucas', 'Harper', 'Henry', 'Evelyn', 'Alexander', 'Abigail', 'Mason', 'Emily',
        'Michael', 'Elizabeth', 'Daniel', 'Sofia', 'Matthew', 'Avery', 'Aiden', 'Ella',
        'Joseph', 'Madison', 'Jackson', 'Scarlett', 'Samuel', 'Victoria', 'Sebastian', 'Aria',
        'David', 'Grace', 'Carter', 'Chloe', 'Wyatt', 'Camila', 'Owen', 'Penelope',
        'Dylan', 'Riley', 'Luke', 'Layla', 'Gabriel', 'Lillian', 'Anthony', 'Nora',
        'Isaac', 'Zoey', 'Grayson', 'Mila', 'Jack', 'Aubrey', 'Julian', 'Hannah',
        'Levi', 'Lillian', 'Christopher', 'Addison', 'Jaxon', 'Eleanor', 'Nathan', 'Natalie',
        'Caleb', 'Luna', 'Ryan', 'Zoe', 'Asher', 'Leah', 'Hunter', 'Hazel',
        'Connor', 'Violet', 'Eli', 'Aurora', 'Aaron', 'Savannah', 'Adrian', 'Audrey',
        'Jeremiah', 'Brooklyn', 'Easton', 'Bella', 'Ezekiel', 'Claire', 'Colton', 'Skylar',
        'Jordan', 'Lucy', 'Brayden', 'Paisley', 'Nicholas', 'Everly', 'Angel', 'Anna',
        'Jace', 'Caroline', 'Dominic', 'Nova', 'Austin', 'Genesis', 'Ian', 'Aaliyah',
        'Adam', 'Kennedy', 'Evan', 'Kinsley', 'Xavier', 'Allison', 'Cooper', 'Maya',
        'Parker', 'Willow', 'Roman', 'Naomi', 'Jason', 'Elena', 'Jose', 'Sarah',
        'Chase', 'Ariana', 'Kevin', 'Quinn', 'Cameron', 'Ivy', 'Thomas', 'Piper',
        'Zachary', 'Lydia', 'Timothy', 'Alexa', 'Blake', 'Josephine', 'Carlos', 'Emilia'
    ];

    // Social media avatars - 50% Facebook, 25% Telegram, 25% X.com
    const socialAvatars = {
        facebook: `<svg viewBox="0 0 24 24" fill="#1877F2" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>`,
        telegram: `<svg viewBox="0 0 24 24" fill="#0088cc" xmlns="http://www.w3.org/2000/svg"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>`,
        twitter: `<svg viewBox="0 0 24 24" fill="#000000" xmlns="http://www.w3.org/2000/svg"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>`
    };

    // Store user avatars to maintain consistency
    const userAvatars = {};

    function getRandomAuthor() {
        return realisticNames[Math.floor(Math.random() * realisticNames.length)];
    }

    function getUserAvatar(author) {
        if (!userAvatars[author]) {
            // 50% Facebook, 25% Telegram, 25% X.com
            const rand = Math.random();
            if (rand < 0.5) {
                userAvatars[author] = 'facebook';
            } else if (rand < 0.75) {
                userAvatars[author] = 'telegram';
            } else {
                userAvatars[author] = 'twitter';
            }
        }
        return socialAvatars[userAvatars[author]];
    }

    // Conversation-based messages that feel like replies (with team names)
    const conversationThreads = [
        {
            messages: [
                { text: `This is going to be a great game between ${homeTeamName} and ${awayTeamName}!`, delay: 0 },
                { text: 'I agree! Both teams look strong', delay: 2000 },
                { text: `Yeah, ${homeTeamName}'s defense is really solid`, delay: 4000 },
                { text: 'Can\'t wait to see how this plays out', delay: 6000 }
            ]
        },
        {
            messages: [
                { text: `What a play by ${homeTeamName}!`, delay: 0 },
                { text: 'That was incredible!', delay: 1500 },
                { text: 'Best play I\'ve seen all season', delay: 3000 }
            ]
        },
        {
            messages: [
                { text: `${awayTeamName} needs to step up their offense`, delay: 0 },
                { text: 'True, they\'re struggling a bit', delay: 2500 },
                { text: 'They\'ll find their rhythm soon', delay: 5000 }
            ]
        },
        {
            messages: [
                { text: `Great defense by ${homeTeamName}!`, delay: 0 },
                { text: 'They\'re really shutting them down', delay: 2000 },
                { text: 'Impressive performance so far', delay: 4000 }
            ]
        },
        {
            messages: [
                { text: `I'm rooting for ${homeTeamName}!`, delay: 0 },
                { text: `Same here! ${awayTeamName} is tough though`, delay: 1800 },
                { text: 'This is going to be close', delay: 3500 }
            ]
        },
        {
            messages: [
                { text: `${awayTeamName} is really bringing the energy!`, delay: 0 },
                { text: 'Amazing atmosphere!', delay: 2000 },
                { text: 'Wish I was there in person', delay: 4000 }
            ]
        },
        {
            messages: [
                { text: `That was a great move by ${homeTeamName}`, delay: 0 },
                { text: 'Yeah, could have gone either way', delay: 2200 },
                { text: 'Refs made the right call though', delay: 4500 }
            ]
        },
        {
            messages: [
                { text: `${homeTeamName} and ${awayTeamName} are playing their hearts out`, delay: 0 },
                { text: 'That\'s what makes this so exciting', delay: 2500 },
                { text: 'True dedication from both sides', delay: 5000 }
            ]
        },
        {
            messages: [
                { text: `${awayTeamName} is making a comeback!`, delay: 0 },
                { text: 'This is getting intense!', delay: 2000 },
                { text: `${homeTeamName} needs to respond`, delay: 4000 }
            ]
        },
        {
            messages: [
                { text: `Both ${homeTeamName} and ${awayTeamName} are playing great`, delay: 0 },
                { text: 'This is championship level play', delay: 2500 },
                { text: 'Could go either way', delay: 5000 }
            ]
        },
        {
            messages: [
                { text: 'Just shared this game on Facebook!', delay: 0 },
                { text: 'Good idea, I\'ll share it too', delay: 2000 },
                { text: 'Everyone needs to see this', delay: 4000 }
            ]
        },
        {
            messages: [
                { text: 'This game is too good not to share', delay: 0 },
                { text: 'I already shared it with my friends', delay: 2500 },
                { text: 'They\'re going to love this', delay: 5000 }
            ]
        },
        {
            messages: [
                { text: 'Just copied the link to share', delay: 0 },
                { text: 'Same here, sending it to my group', delay: 1800 },
                { text: 'This is share-worthy content', delay: 3500 }
            ]
        },
        {
            messages: [
                { text: 'My friends need to watch this!', delay: 0 },
                { text: 'I\'m sharing it on all my socials', delay: 2000 },
                { text: 'This needs to go viral', delay: 4000 }
            ]
        },
        {
            messages: [
                { text: `The intensity between ${homeTeamName} and ${awayTeamName} is off the charts!`, delay: 0 },
                { text: 'Absolutely! This is what sports is all about', delay: 2200 },
                { text: 'Both teams are giving 110%', delay: 4500 }
            ]
        },
        {
            messages: [
                { text: `That was a clutch play by ${homeTeamName}!`, delay: 0 },
                { text: 'Game changer right there!', delay: 1800 },
                { text: 'Momentum shift for sure', delay: 3800 }
            ]
        },
        {
            messages: [
                { text: `${awayTeamName} is showing incredible resilience`, delay: 0 },
                { text: 'They never give up, that\'s impressive', delay: 2500 },
                { text: 'True champions mentality', delay: 5000 }
            ]
        },
        {
            messages: [
                { text: `The chemistry between ${homeTeamName}'s players is amazing`, delay: 0 },
                { text: 'You can see they trust each other', delay: 2000 },
                { text: 'That teamwork is paying off', delay: 4200 }
            ]
        },
        {
            messages: [
                { text: `This ${homeTeamName} vs ${awayTeamName} matchup is legendary!`, delay: 0 },
                { text: 'Going down in history for sure', delay: 2300 },
                { text: 'Future generations will talk about this', delay: 4800 }
            ]
        },
        {
            messages: [
                { text: `The crowd is going wild for ${homeTeamName}!`, delay: 0 },
                { text: 'The energy is contagious!', delay: 1900 },
                { text: 'Wish I could feel that atmosphere', delay: 4000 }
            ]
        },
        {
            messages: [
                { text: `${awayTeamName} just made an incredible save!`, delay: 0 },
                { text: 'That was world class!', delay: 2100 },
                { text: 'Saved the game right there', delay: 4400 }
            ]
        },
        {
            messages: [
                { text: `Both ${homeTeamName} and ${awayTeamName} deserve respect`, delay: 0 },
                { text: 'No matter who wins, both are winners', delay: 2600 },
                { text: 'This is sportsmanship at its finest', delay: 5200 }
            ]
        },
        {
            messages: [
                { text: 'The strategy from both teams is brilliant', delay: 0 },
                { text: 'You can see the coaching is top notch', delay: 2400 },
                { text: 'Every move is calculated', delay: 4900 }
            ]
        },
        {
            messages: [
                { text: `I've been waiting all week for ${homeTeamName} vs ${awayTeamName}!`, delay: 0 },
                { text: 'Same! This is the highlight of my week', delay: 2000 },
                { text: 'Worth every second of the wait', delay: 4100 }
            ]
        },
        {
            messages: [
                { text: 'The skill level on display is insane!', delay: 0 },
                { text: 'These are elite athletes for sure', delay: 2200 },
                { text: 'Top tier performance all around', delay: 4600 }
            ]
        },
        {
            messages: [
                { text: `Every play from ${homeTeamName} is executed perfectly`, delay: 0 },
                { text: 'The precision is remarkable', delay: 2100 },
                { text: 'They\'ve clearly practiced hard', delay: 4300 }
            ]
        },
        {
            messages: [
                { text: `This game between ${homeTeamName} and ${awayTeamName} is must-see TV!`, delay: 0 },
                { text: 'Everyone should be watching this', delay: 2300 },
                { text: 'This is why I love sports', delay: 4700 }
            ]
        },
        {
            messages: [
                { text: 'The tension is building with every play!', delay: 0 },
                { text: 'My heart is racing!', delay: 1800 },
                { text: 'This is edge-of-your-seat stuff', delay: 3900 }
            ]
        }
    ];

    // Q&A Threads - Questions and Answers (Countdown/Upcoming specific)
    // These are only shown during countdown, focused on event schedule and upcoming game
    const qaThreadsCountdown = []; // Questions about when game starts (only for countdown)
    const qaThreadsGeneral = []; // General questions (can be shown for on-demand too)

    // Build Q&A threads dynamically based on available event data
    // Countdown-specific questions (only shown when countdown is visible)
    if (eventDateTime.time) {
        qaThreadsCountdown.push({
            question: `What time does ${homeTeamName} vs ${awayTeamName} start?`,
            answers: [
                { text: `The game starts at ${eventDateTime.time}!`, delay: 2000 },
                { text: `It's scheduled for ${eventDateTime.time}, check the countdown timer`, delay: 3000 },
                { text: `${eventDateTime.time} - you can see the countdown above`, delay: 4000 }
            ]
        });
    }

    if (eventDateTime.date) {
        qaThreadsCountdown.push({
            question: `When is the ${homeTeamName} vs ${awayTeamName} game?`,
            answers: [
                { text: `${eventDateTime.date} at ${eventDateTime.time || 'the scheduled time'}`, delay: 2000 },
                { text: `It's on ${eventDateTime.date}`, delay: 3000 },
                { text: `Check the countdown - it's ${eventDateTime.date}`, delay: 4000 }
            ]
        });
    }

    if (eventDateTime.fullDate) {
        qaThreadsCountdown.push({
            question: `What's the exact date and time for this game?`,
            answers: [
                { text: `${eventDateTime.fullDate}`, delay: 2000 },
                { text: `It's ${eventDateTime.fullDate} - you can see it in the event details`, delay: 3000 },
                { text: `${eventDateTime.fullDate} - the countdown shows how long until it starts`, delay: 4000 }
            ]
        });
    }

    qaThreadsCountdown.push(
        {
            question: `How long until ${homeTeamName} vs ${awayTeamName} starts?`,
            answers: [
                { text: 'Check the countdown timer above the player!', delay: 2000 },
                { text: 'The countdown shows exactly when it starts', delay: 3000 },
                { text: 'Look at the timer - it shows days, hours, and minutes', delay: 4000 }
            ]
        },
        {
            question: `When does the ${homeTeamName} vs ${awayTeamName} game begin?`,
            answers: eventDateTime.time ? [
                { text: `${eventDateTime.time} - you can see the countdown`, delay: 2000 },
                { text: `It starts at ${eventDateTime.time}`, delay: 3000 },
                { text: `Scheduled for ${eventDateTime.time}, check the timer above`, delay: 4000 }
            ] : [
                { text: 'Check the countdown timer above the video player', delay: 2000 },
                { text: 'The timer shows when the game will start', delay: 3000 },
                { text: 'Look at the countdown - it shows the exact time', delay: 4000 }
            ]
        },
        {
            question: `Is the ${homeTeamName} vs ${awayTeamName} game today?`,
            answers: [
                { text: 'Check the date in the event details above', delay: 2000 },
                { text: 'The countdown timer shows how long until it starts', delay: 3000 },
                { text: 'Look at the event date to see if it\'s today', delay: 4000 }
            ]
        },
        {
            question: `What time is the ${homeTeamName} game?`,
            answers: eventDateTime.time ? [
                { text: `${eventDateTime.time} - check the countdown!`, delay: 2000 },
                { text: `It's at ${eventDateTime.time}`, delay: 3000 },
                { text: `${eventDateTime.time}, you can see it in the details`, delay: 4000 }
            ] : [
                { text: 'Check the countdown timer for the exact time', delay: 2000 },
                { text: 'The event details show the scheduled time', delay: 3000 },
                { text: 'Look at the countdown above the player', delay: 4000 }
            ]
        },
        {
            question: `When will ${awayTeamName} vs ${homeTeamName} be live?`,
            answers: eventDateTime.fullDate ? [
                { text: `${eventDateTime.fullDate} - watch the countdown!`, delay: 2000 },
                { text: `It goes live on ${eventDateTime.fullDate}`, delay: 3000 },
                { text: `${eventDateTime.fullDate} - the timer shows when`, delay: 4000 }
            ] : [
                { text: 'The countdown timer shows when it goes live', delay: 2000 },
                { text: 'Check the event details for the exact time', delay: 3000 },
                { text: 'The timer above shows when it starts', delay: 4000 }
            ]
        },
        {
            question: `Can I watch ${homeTeamName} vs ${awayTeamName} later?`,
            answers: [
                { text: 'Yes! It will be available on demand after it airs', delay: 2000 },
                { text: 'You can watch the replay after the live game ends', delay: 3000 },
                { text: 'On demand will be available after the game finishes', delay: 4000 }
            ]
        },
        {
            question: `How do I get notified when ${homeTeamName} vs ${awayTeamName} starts?`,
            answers: [
                { text: 'You can share the link and set a reminder', delay: 2000 },
                { text: 'Bookmark this page and check back at game time', delay: 3000 },
                { text: 'Share it to your calendar or set an alarm', delay: 4000 }
            ]
        },
        {
            question: `Will ${homeTeamName} vs ${awayTeamName} be streamed live?`,
            answers: [
                { text: 'Yes! This will be a live stream when it starts', delay: 2000 },
                { text: 'It\'s scheduled for live streaming', delay: 3000 },
                { text: 'Yes, you can watch it live when the countdown ends', delay: 4000 }
            ]
        },
        {
            question: `What happens when the countdown reaches zero?`,
            answers: [
                { text: 'The game will start and you can watch it live!', delay: 2000 },
                { text: 'The live stream will begin automatically', delay: 3000 },
                { text: `You'll be able to watch ${homeTeamName} vs ${awayTeamName} live`, delay: 4000 }
            ]
        },
        {
            question: `Is the ${homeTeamName} vs ${awayTeamName} game still upcoming?`,
            answers: [
                { text: 'Yes! Check the countdown - it hasn\'t started yet', delay: 2000 },
                { text: 'The countdown timer shows it\'s still upcoming', delay: 3000 },
                { text: 'Yes, it\'s scheduled for the future - see the timer', delay: 4000 }
            ]
        }
    );

    // General Q&A (can be shown for on-demand/live too)
    // Add location-based Q&A if location is available
    if (locationText && locationText.trim() !== '') {
        qaThreadsGeneral.push({
            question: `Where is ${homeTeamName} vs ${awayTeamName} being played?`,
            answers: [
                { text: `${locationText}`, delay: 2000 },
                { text: `The game is at ${locationText}`, delay: 3000 },
                { text: `It's being played at ${locationText}`, delay: 4000 }
            ]
        });
    }

    // Add general questions relevant for live/on-demand events
    qaThreadsGeneral.push(
        {
            question: `Who's winning ${homeTeamName} vs ${awayTeamName}?`,
            answers: [
                { text: 'Check the score above the video player!', delay: 2000 },
                { text: 'The score is displayed in the event details', delay: 3000 },
                { text: 'Look at the top of the page for the current score', delay: 4000 }
            ]
        },
        {
            question: `How can I watch ${homeTeamName} vs ${awayTeamName}?`,
            answers: [
                { text: 'You can watch it right here on this page!', delay: 2000 },
                { text: 'Just click the play button to start watching', delay: 3000 },
                { text: 'The video player is right above, click play', delay: 4000 }
            ]
        },
        {
            question: `Is ${homeTeamName} vs ${awayTeamName} available on demand?`,
            answers: [
                { text: 'Yes! You can watch it anytime after it airs', delay: 2000 },
                { text: 'On demand is available right here on this page', delay: 3000 },
                { text: 'You can replay it anytime you want', delay: 4000 }
            ]
        },
        {
            question: `Can I share this ${homeTeamName} vs ${awayTeamName} game?`,
            answers: [
                { text: 'Yes! Use the share buttons below the player', delay: 2000 },
                { text: 'You can share on Facebook, Twitter, WhatsApp, and more', delay: 3000 },
                { text: 'Just click the share icon to share with friends', delay: 4000 }
            ]
        },
        {
            question: `What sport is ${homeTeamName} vs ${awayTeamName}?`,
            answers: [
                { text: `Check the event details above - it shows the sport type`, delay: 2000 },
                { text: `The discipline is shown in the event information`, delay: 3000 },
                { text: `Look at the event header for sport details`, delay: 4000 }
            ]
        },
        {
            question: `How long is the ${homeTeamName} vs ${awayTeamName} game?`,
            answers: [
                { text: 'Game length varies by sport - check the event details', delay: 2000 },
                { text: 'The duration depends on the sport being played', delay: 3000 },
                { text: 'Watch the video to see the full game length', delay: 4000 }
            ]
        },
        {
            question: `Can I watch ${homeTeamName} vs ${awayTeamName} on mobile?`,
            answers: [
                { text: 'Yes! This page works great on mobile devices', delay: 2000 },
                { text: 'You can watch on any device - phone, tablet, or computer', delay: 3000 },
                { text: 'Just open this page on your mobile browser', delay: 4000 }
            ]
        },
        {
            question: `Is there a replay of ${homeTeamName} vs ${awayTeamName}?`,
            answers: [
                { text: 'Yes! You can watch the replay anytime', delay: 2000 },
                { text: 'The full game is available on demand', delay: 3000 },
                { text: 'Just click play to watch the replay', delay: 4000 }
            ]
        },
        {
            question: `What teams are playing in this game?`,
            answers: [
                { text: `${homeTeamName} vs ${awayTeamName}`, delay: 2000 },
                { text: `It's ${homeTeamName} playing against ${awayTeamName}`, delay: 3000 },
                { text: `The matchup is ${homeTeamName} versus ${awayTeamName}`, delay: 4000 }
            ]
        },
        {
            question: `How do I get notifications for ${homeTeamName} games?`,
            answers: [
                { text: 'Share this page and bookmark it for updates', delay: 2000 },
                { text: 'You can set reminders by sharing to your calendar', delay: 3000 },
                { text: 'Bookmark this page to stay updated', delay: 4000 }
            ]
        }
    );

    // Combine all Q&A threads for easy access
    const qaThreads = [...qaThreadsCountdown, ...qaThreadsGeneral];

    // General messages - Different for countdown vs live
    const gameMessages = isEventStarted ? [
        // Messages for LIVE game
        `This is going to be a great game between ${homeTeamName} and ${awayTeamName}!`,
        `Both ${homeTeamName} and ${awayTeamName} look strong today`,
        'What a fantastic play!',
        `The defense is really stepping up`,
        'Incredible performance so far',
        'This is so intense!',
        'Can\'t believe that move',
        'The crowd is electric!',
        'Best game I\'ve watched this season',
        'They\'re really giving it their all',
        'What a shot!',
        'That was close!',
        `The offense is on fire`,
        'Great teamwork on display',
        'This is nail-biting stuff',
        'Amazing athleticism',
        'They\'re playing with so much heart',
        `What a comeback!`,
        'The momentum is shifting',
        'This is championship level play',
        'Incredible defense!',
        'They\'re really fighting for this',
        'What a game!',
        'The intensity is unreal',
        `Both ${homeTeamName} and ${awayTeamName} deserve credit`,
        `${homeTeamName} is really bringing it today!`,
        `${awayTeamName} needs to respond`,
        `I'm impressed with ${homeTeamName}'s performance`,
        `${awayTeamName} is showing great resilience`,
        `This matchup between ${homeTeamName} and ${awayTeamName} is epic!`,
        `${homeTeamName} is dominating right now`,
        `${awayTeamName} is fighting back hard`,
        `Both teams are giving everything they have`,
        `${homeTeamName} vs ${awayTeamName} - what a game!`,
        'The skill level is through the roof!',
        'Every play is executed to perfection',
        'This is elite-level competition',
        'The strategy is brilliant on both sides',
        'World-class performance happening right now',
        'The precision is remarkable',
        'This is what peak performance looks like',
        'The athleticism on display is insane',
        'These players are in the zone',
        'The execution is flawless',
        'This is masterclass level play',
        'The determination is inspiring',
        'Every moment is crucial',
        'The pressure is intense but they\'re handling it',
        'This is why I love watching sports',
        'The passion is evident in every play',
        'This game is delivering everything I hoped for',
        'The energy is absolutely electric',
        'This is edge-of-your-seat action',
        'The competition is fierce',
        'Both teams are leaving it all on the field',
        'This is a battle for the ages',
        'The heart and soul of both teams is showing',
        'This is must-see television',
        'The level of play is extraordinary',
        'This is what championship games are made of',
        'The intensity never lets up',
        'This is pure sports entertainment',
        'The drama is unfolding perfectly',
        'This is a showcase of talent',
        // Share topic messages
        'Just shared this game on Facebook!',
        'Sharing this epic game with my friends',
        'Everyone needs to see this game!',
        'Just posted this on Twitter',
        'Sharing the link with my group chat',
        'This game is too good not to share',
        'My friends need to watch this!',
        'Just copied the link to share',
        'Sharing this on all my socials',
        'Can\'t wait to share this with everyone',
        'This is share-worthy content!',
        'Just sent this to my friends',
        'Everyone should see this game',
        'Sharing this amazing game now',
        'This needs to go viral!',
        'Just shared on WhatsApp',
        'My timeline needs to see this',
        'Sharing this incredible game!',
        'This is too good to keep to myself',
        'Just posted about this game',
        'Sharing this legendary matchup!',
        'My followers need to see this',
        'Just shared on Instagram stories',
        'This game deserves more viewers',
        'Sharing with my sports group',
        'Everyone needs to witness this',
        'Just sent the link to my family',
        'This is going on my story',
        'Sharing this epic moment',
        'My friends are going to love this'
    ] : [
        // Messages for COUNTDOWN (waiting)
        `Can't wait for ${homeTeamName} vs ${awayTeamName}!`,
        `This matchup is going to be epic!`,
        'The anticipation is real!',
        'Almost time for the game to start',
        `I'm so excited for this!`,
        'Who else is waiting?',
        `This is going to be a great game`,
        'The wait is almost over!',
        `Both ${homeTeamName} and ${awayTeamName} are ready`,
        'Getting hyped for this!',
        'So close to game time!',
        `I've been looking forward to this`,
        'The countdown is on!',
        `Who's going to win? ${homeTeamName} or ${awayTeamName}?`,
        'This is going to be worth the wait',
        'The energy is building!',
        `I'm rooting for ${homeTeamName}!`,
        `I think ${awayTeamName} has a good chance`,
        'Can\'t wait to see the action',
        'The excitement is real!',
        `This ${homeTeamName} vs ${awayTeamName} game is going to be intense`,
        'Almost there!',
        'Getting ready for an amazing game',
        `Both teams are going to bring it`,
        'The anticipation is killing me!',
        `I'm so ready for ${homeTeamName} vs ${awayTeamName}!`,
        'This is going to be a battle',
        'The wait will be worth it!',
        `I can't wait to see ${homeTeamName} play`,
        `${awayTeamName} is going to be tough to beat`,
        // Share topic messages for countdown
        'Just shared the link with my friends',
        'Sharing this game so everyone can watch',
        'My friends need to know about this game',
        'Just posted about this on social media',
        'Sharing the countdown with everyone',
        'This game is going to be amazing, sharing now!',
        'Just copied the link to share',
        'Everyone should watch this game',
        'Sharing this with my group',
        'My timeline needs to see this',
        'Just shared on Facebook',
        'Sharing this epic matchup!',
        'Can\'t wait to share this game',
        'This is share-worthy!',
        'Just sent the link to my friends',
        'Sharing on all platforms',
        'Everyone needs to see this!',
        'Just shared on WhatsApp',
        'This game is too good not to share',
        'Sharing the link now!',
        'My followers need to know about this',
        'Just posted on my story',
        'Sharing this with my sports community',
        'Everyone should mark their calendars',
        'Just sent this to my group chat',
        'This is going to be legendary',
        'Sharing the excitement with everyone',
        'My friends are going to love this',
        'Just shared on all my accounts',
        'This matchup is too good to miss',
        'Sharing this countdown everywhere',
        'Everyone needs to be here for this',
        'Just posted about the upcoming game',
        'Sharing this epic event',
        'My network needs to see this'
    ];

    let currentThreadIndex = 0;
    let threadMessageIndex = 0;
    let lastAuthor = null;
    let conversationActive = false;
    let currentQAThread = null;
    let qaAnswerIndex = 0;
    let qaActive = false;
    let questionAuthor = null; // Store the author who asked the question
    let lastQuestionTime = 0; // Track when last question was asked
    const minQuestionInterval = 35000; // Maximum 35 seconds (35000ms) between questions - optimized for more frequent Q&A

    // Message cooldown tracking system (3 minutes = 180000 milliseconds)
    const messageCooldown = 180000; // 3 minutes in milliseconds
    const usedMessages = new Map(); // Map to store message text -> timestamp when it was used

    // Q&A question cooldown tracking system (5 minutes = 300000 milliseconds)
    const qaQuestionCooldown = 300000; // 5 minutes in milliseconds - longer than message cooldown to prevent repetition
    const usedQaQuestions = new Map(); // Map to store Q&A question text -> timestamp when it was used

    // Helper function to filter messages based on cooldown
    function getAvailableMessages(messages) {
        const now = Date.now();
        return messages.filter(msg => {
            const lastUsed = usedMessages.get(msg);
            if (!lastUsed) return true; // Never used, available
            return (now - lastUsed) >= messageCooldown; // Check if cooldown has passed
        });
    }

    // Helper function to get available conversation threads
    function getAvailableThreads(threads) {
        const now = Date.now();
        return threads.filter(thread => {
            // Check if any message in the thread was recently used
            const threadKey = thread.messages.map(m => m.text).join('|');
            const lastUsed = usedMessages.get(threadKey);
            if (!lastUsed) return true; // Never used, available
            return (now - lastUsed) >= messageCooldown; // Check if cooldown has passed
        });
    }

    // Helper function to filter Q&A questions based on cooldown
    function getAvailableQaQuestions(qaThreads) {
        const now = Date.now();
        return qaThreads.filter(qaThread => {
            const questionText = qaThread.question;
            const lastUsed = usedQaQuestions.get(questionText);
            if (!lastUsed) return true; // Never used, available
            return (now - lastUsed) >= qaQuestionCooldown; // Check if cooldown has passed (5 minutes)
        });
    }

    function getContextualMessage() {
        // Check current event status to use appropriate messages
        const currentIsStarted = checkEventStatus();

        // Get the appropriate conversation threads and messages based on current status
        // Note: conversationThreads and gameMessages are already set based on initial isEventStarted
        // But we need to check dynamically
        const activeThreads = currentIsStarted ? conversationThreads : conversationThreads;
        const activeMessages = currentIsStarted ? gameMessages : gameMessages;

        // Q&A appears during countdown OR when play button is visible (on-demand/replay)
        // Optimized: Ensure at least 1 question every 35 seconds, with higher probability
        const showQA = shouldShowQA();
        const now = Date.now();
        const timeSinceLastQuestion = now - lastQuestionTime;
        const shouldForceQuestion = timeSinceLastQuestion >= minQuestionInterval;

        // Force question if interval passed, or 45% chance otherwise (increased for more frequent Q&A)
        const questionChance = shouldForceQuestion ? 1.0 : 0.45; // Increased from 25% to 45% for more frequent Q&A

        if (showQA && !qaActive && Math.random() < questionChance) {
            // Check if countdown is visible to determine which Q&A threads to use
            const countdownTimer = document.getElementById('countdownTimer');
            const countdownVisible = countdownTimer &&
                countdownTimer.style.display !== 'none' &&
                window.getComputedStyle(countdownTimer).display !== 'none';

            // Use countdown-specific questions only if countdown is visible
            // Otherwise use general questions (or no Q&A if event is live)
            const allQaThreads = countdownVisible ? qaThreadsCountdown : qaThreadsGeneral;

            // Filter out recently used questions to prevent repetition
            const availableQaThreads = getAvailableQaQuestions(allQaThreads);
            const threadsToUse = availableQaThreads.length > 0 ? availableQaThreads : allQaThreads;

            if (threadsToUse.length > 0) {
                const qaIndex = Math.floor(Math.random() * threadsToUse.length);
                currentQAThread = threadsToUse[qaIndex];
                qaAnswerIndex = 0;
                qaActive = true;
                conversationActive = false;
                lastQuestionTime = now; // Update last question time
                // Track this question as used to prevent repetition
                usedQaQuestions.set(currentQAThread.question, now);
                return { type: 'question', text: currentQAThread.question };
            }
        }

        // 40% chance of continuing a conversation thread (reduced to make more room for Q&A)
        if (Math.random() < 0.4 && activeThreads.length > 0) {
            // Get available threads (not in cooldown)
            const availableThreads = getAvailableThreads(activeThreads);
            const threadsToUse = availableThreads.length > 0 ? availableThreads : activeThreads;

            const thread = threadsToUse[currentThreadIndex % threadsToUse.length];
            if (threadMessageIndex < thread.messages.length) {
                const message = thread.messages[threadMessageIndex];
                threadMessageIndex++;
                conversationActive = true;
                return { type: 'message', text: message.text };
            } else {
                // Move to next thread
                currentThreadIndex = (currentThreadIndex + 1) % threadsToUse.length;
                threadMessageIndex = 0;
                conversationActive = false;
            }
        }

        // Before using general messages, check Q&A again (if not forced but should have chance)
        // This ensures Q&A doesn't get skipped by conversation threads
        if (showQA && !qaActive) {
            // Higher chance if close to interval, lower if just asked
            const timeRatio = timeSinceLastQuestion / minQuestionInterval;
            const questionChance = Math.min(0.6, 0.3 + (timeRatio * 0.3)); // 30-60% based on time elapsed

            if (Math.random() < questionChance) {
                // Check if countdown is visible to determine which Q&A threads to use
                const countdownTimer = document.getElementById('countdownTimer');
                const countdownVisible = countdownTimer &&
                    countdownTimer.style.display !== 'none' &&
                    window.getComputedStyle(countdownTimer).display !== 'none';

                // Use countdown-specific questions only if countdown is visible
                // Otherwise use general questions (or no Q&A if event is live)
                const allQaThreads = countdownVisible ? qaThreadsCountdown : qaThreadsGeneral;

                // Filter out recently used questions
                const availableQaThreads = getAvailableQaQuestions(allQaThreads);
                const threadsToUse = availableQaThreads.length > 0 ? availableQaThreads : allQaThreads;

                if (threadsToUse.length > 0) {
                    const qaIndex = Math.floor(Math.random() * threadsToUse.length);
                    currentQAThread = threadsToUse[qaIndex];
                    qaAnswerIndex = 0;
                    qaActive = true;
                    conversationActive = false;
                    lastQuestionTime = now; // Update last question time
                    // Track this question as used
                    usedQaQuestions.set(currentQAThread.question, now);
                    return { type: 'question', text: currentQAThread.question };
                }
            }
        }

        // Use general game messages - filter by cooldown
        conversationActive = false;
        const availableMessages = getAvailableMessages(activeMessages);
        const messagesToUse = availableMessages.length > 0 ? availableMessages : activeMessages;
        const selectedMessage = messagesToUse[Math.floor(Math.random() * messagesToUse.length)];
        return { type: 'message', text: selectedMessage };
    }

    function getNextAuthor() {
        // 40% chance to continue conversation with same author
        if (conversationActive && lastAuthor && Math.random() < 0.4) {
            return lastAuthor;
        }
        // Otherwise pick random author
        lastAuthor = getRandomAuthor();
        return lastAuthor;
    }

    // Function to show typing indicator
    function showTypingIndicator(author) {
        // Remove any existing typing indicator first
        const existingTyping = chatMessages.querySelector('.live-chat-typing');
        if (existingTyping) {
            existingTyping.remove();
        }

        const typingDiv = document.createElement('div');
        typingDiv.className = 'live-chat-typing';
        typingDiv.setAttribute('data-author', author);

        const avatarSvg = getUserAvatar(author);

        typingDiv.innerHTML = `
                    <div class="live-chat-typing-avatar">${avatarSvg}</div>
                    <div class="live-chat-typing-content">
                        <span class="live-chat-typing-author">${author}</span>
                        <span class="live-chat-typing-dots">
                            <span class="live-chat-typing-dot"></span>
                            <span class="live-chat-typing-dot"></span>
                            <span class="live-chat-typing-dot"></span>
                        </span>
                    </div>
                `;

        chatMessages.appendChild(typingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        return typingDiv;
    }

    // Function to remove typing indicator
    function removeTypingIndicator(author) {
        const typingIndicator = chatMessages.querySelector(`.live-chat-typing[data-author="${author}"]`);
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    function addChatMessage(author, text, color, showTyping = true) {
        // All messages use typing indicator by default (showTyping = true)
        // Calculate typing delay based on message length (simulate natural typing speed)
        // Average typing speed: ~80ms per character, minimum 800ms, maximum 3000ms
        const typingDelay = showTyping ? Math.min(3000, Math.max(800, text.length * 80)) : 0;

        // Show typing indicator if enabled
        let typingIndicator = null;
        if (showTyping && typingDelay > 0) {
            typingIndicator = showTypingIndicator(author);
        }

        // Schedule message to appear after typing delay
        setTimeout(() => {
            // Remove typing indicator before showing message
            if (typingIndicator) {
                removeTypingIndicator(author);
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = 'live-chat-message';

            const avatarSvg = getUserAvatar(author);

            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const timestamp = `${hours}:${minutes}`;

            // Process mentions in text (format: @username)
            let processedText = text;
            const mentionRegex = /@(\w+)/g;
            processedText = processedText.replace(mentionRegex, '<span class="mention">@$1</span>');

            messageDiv.innerHTML = `
                        <div class="live-chat-avatar">${avatarSvg}</div>
                        <div class="live-chat-content">
                            <div>
                                <span class="live-chat-author">${author}</span>
                                <span class="live-chat-timestamp">${timestamp}</span>
                            </div>
                            <div class="live-chat-text">${processedText}</div>
                        </div>
                    `;

            chatMessages.appendChild(messageDiv);

            // Track message usage for cooldown system (3 minutes)
            const nowTimestamp = Date.now();
            usedMessages.set(text, nowTimestamp);

            // Clean up old entries (older than cooldown period) to prevent memory leaks
            // Only clean up occasionally to avoid performance issues
            if (Math.random() < 0.1) { // 10% chance to clean up
                const cleanupTime = nowTimestamp - messageCooldown;
                for (const [msg, timestamp] of usedMessages.entries()) {
                    if (timestamp < cleanupTime) {
                        usedMessages.delete(msg);
                    }
                }

                // Also clean up old Q&A questions (older than 5 minutes)
                const qaCleanupTime = nowTimestamp - qaQuestionCooldown;
                for (const [question, timestamp] of usedQaQuestions.entries()) {
                    if (timestamp < qaCleanupTime) {
                        usedQaQuestions.delete(question);
                    }
                }
            }

            // Remove the first (oldest/top) message if there are messages
            const messages = chatMessages.querySelectorAll('.live-chat-message');
            if (messages.length > 20) {
                messages[0].remove();
            }

            // Auto scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, typingDelay);
    }

    function updateViewerCount() {
        if (!chatViewerCount) return;
        // Range: 85-500, simulate viewers coming and going
        // Get current count from display or use base
        const currentText = chatViewerCount.textContent || '250 watching';
        const currentCount = parseInt(currentText.replace(/[^0-9]/g, '')) || 250;

        // Random change between -20 and +20 to simulate natural fluctuation
        const randomChange = Math.floor(Math.random() * 41) - 20; // -20 to +20
        const newCount = Math.max(85, Math.min(500, currentCount + randomChange));
        chatViewerCount.textContent = newCount + ' watching';
    }

    // Add initial messages - Start with 1 Q&A first to ensure queue works correctly
    setTimeout(() => {
        let qaWasShown = false;

        // Try to show Q&A first (simplified logic)
        if (shouldShowQA && typeof shouldShowQA === 'function') {
            try {
                const showQA = shouldShowQA();
                if (showQA) {
                    const countdownTimer = document.getElementById('countdownTimer');
                    const countdownVisible = countdownTimer &&
                        countdownTimer.style.display !== 'none' &&
                        window.getComputedStyle(countdownTimer).display !== 'none';

                    const allQaThreads = countdownVisible ? qaThreadsCountdown : qaThreadsGeneral;

                    if (allQaThreads && allQaThreads.length > 0 && !qaActive) {
                        const qaIndex = Math.floor(Math.random() * allQaThreads.length);
                        currentQAThread = allQaThreads[qaIndex];

                        if (currentQAThread && currentQAThread.question) {
                            qaAnswerIndex = 0;
                            qaActive = true;
                            qaWasShown = true;
                            const questionAuthor = getNextAuthor();
                            const now = Date.now();
                            lastQuestionTime = now;

                            // Track this question as used
                            usedQaQuestions.set(currentQAThread.question, now);

                            // Add the question
                            addChatMessage(questionAuthor, currentQAThread.question);

                            // Schedule first answer after 3-5 seconds
                            const firstAnswerDelay = 3000 + Math.random() * 2000;
                            const storedQuestionAuthor = questionAuthor;
                            setTimeout(() => {
                                if (currentQAThread && currentQAThread.answers && currentQAThread.answers.length > 0) {
                                    const answerAuthor = getNextAuthor();
                                    const answerText = `@${storedQuestionAuthor} ${currentQAThread.answers[0].text}`;
                                    addChatMessage(answerAuthor, answerText);
                                }
                                qaActive = false;
                                currentQAThread = null;
                                qaAnswerIndex = 0;
                            }, firstAnswerDelay);
                        }
                    }
                }
            } catch (e) {
                console.error('Error in initial Q&A:', e);
            }
        }

        // Always add normal messages (guaranteed to work)
        const normalMessageStartDelay = qaWasShown ? 6000 : 1500;
        const numNormalMessages = qaWasShown ? 4 : 5;

        for (let i = 0; i < numNormalMessages; i++) {
            setTimeout(() => {
                const author = getNextAuthor();
                const currentIsStarted = checkEventStatus();
                const activeMessages = currentIsStarted ? gameMessages : gameMessages;

                if (activeMessages && activeMessages.length > 0) {
                    const normalText = activeMessages[Math.floor(Math.random() * activeMessages.length)];
                    // Enable typing indicator for all initial messages
                    addChatMessage(author, normalText, null, true);
                }
            }, normalMessageStartDelay + (i * 800));
        }
    }, 1500);

    // Add new messages periodically with conversation flow and Q&A support
    function addNextMessage() {
        // Check if Q&A should be shown - if not, cancel any active Q&A
        const showQA = shouldShowQA();
        if (!showQA && qaActive) {
            qaActive = false;
            currentQAThread = null;
            qaAnswerIndex = 0;
            questionAuthor = null;
        }

        const author = getNextAuthor();
        const messageResult = getContextualMessage();

        // Check if this is a question (Q&A thread) - only if Q&A should be shown
        if (messageResult.type === 'question' && showQA) {
            // Store the question author
            questionAuthor = author;
            // Update last question time
            lastQuestionTime = Date.now();
            // Add the question immediately
            addChatMessage(author, messageResult.text);

            // Schedule answers with their delays and mentions
            // Make it more natural: not all questions get all answers, and answers are more spread out
            if (currentQAThread && currentQAThread.answers) {
                // Randomly decide how many answers this question will get (1-3 answers, weighted towards 1-2)
                const numAnswers = Math.random() < 0.4 ? 1 : (Math.random() < 0.7 ? 2 : 3);
                const selectedAnswers = [];

                // Randomly select which answers to show
                const shuffled = [...currentQAThread.answers].sort(() => Math.random() - 0.5);
                for (let i = 0; i < numAnswers && i < shuffled.length; i++) {
                    selectedAnswers.push(shuffled[i]);
                }

                // First answer appears after initial delay (3-6 seconds - optimized for faster response)
                // All answers must appear within 40 seconds maximum (optimized from 60 seconds)
                const maxTotalDelay = 40000; // 40 seconds in milliseconds (optimized)
                const firstAnswerDelay = 3000 + Math.random() * 3000; // 3-6 seconds (optimized from 5-10)
                let cumulativeDelay = firstAnswerDelay;

                // Schedule first answer
                setTimeout(() => {
                    const answerAuthor = getNextAuthor();
                    const answerText = `@${questionAuthor} ${selectedAnswers[0].text}`;
                    addChatMessage(answerAuthor, answerText);
                }, cumulativeDelay);

                // Schedule subsequent answers with delays that ensure all finish within 40 seconds (optimized)
                for (let i = 1; i < selectedAnswers.length; i++) {
                    // Calculate remaining time and distribute delays evenly
                    const remainingAnswers = selectedAnswers.length - i;
                    const remainingTime = maxTotalDelay - cumulativeDelay;
                    // Distribute remaining time among remaining answers, with some randomness
                    const delayBetweenAnswers = remainingTime / (remainingAnswers + 1);
                    // Add 6-12 seconds between answers (optimized from 10-20), but ensure we don't exceed 40 seconds total
                    const nextDelay = Math.min(
                        delayBetweenAnswers + (Math.random() * 3000 - 1500), // Add some randomness
                        12000 // Cap at 12 seconds between answers (optimized from 20)
                    );
                    cumulativeDelay += Math.max(6000, nextDelay); // Minimum 6 seconds between answers (optimized from 10)

                    // Ensure we don't exceed 40 seconds total
                    if (cumulativeDelay > maxTotalDelay) {
                        cumulativeDelay = maxTotalDelay - (remainingAnswers * 1500); // Reserve 1.5 seconds per remaining answer
                    }

                    setTimeout(() => {
                        const answerAuthor = getNextAuthor();
                        const answerText = `@${questionAuthor} ${selectedAnswers[i].text}`;
                        addChatMessage(answerAuthor, answerText);
                    }, cumulativeDelay);
                }

                // Schedule next message after all answers are done
                const lastAnswerDelay = cumulativeDelay;
                setTimeout(() => {
                    // Reset Q&A state after answers are done
                    qaActive = false;
                    currentQAThread = null;
                    qaAnswerIndex = 0;
                    questionAuthor = null;
                    addNextMessage();
                }, lastAnswerDelay + 4000 + Math.random() * 2000);
            } else {
                // Fallback if no answers
                setTimeout(() => {
                    qaActive = false;
                    currentQAThread = null;
                    qaAnswerIndex = 0;
                    questionAuthor = null;
                    addNextMessage();
                }, 3000 + Math.random() * 2000);
            }
        } else {
            // Normal message
            addChatMessage(author, messageResult.text);

            // Schedule next message
            setTimeout(() => {
                addNextMessage();
            }, 2500 + Math.random() * 3000);
        }
    }

    // Start the message loop after initial messages
    setTimeout(() => {
        addNextMessage();
    }, 5000);

    // Update viewer count periodically
    updateViewerCount();
    setInterval(updateViewerCount, 10000); // Every 10 seconds

    // CTA Login for live-chat-input-container
    const chatInputContainer = document.querySelector('.live-chat-input-container');
    const liveChatCTAModal = document.getElementById('liveChatCTAModal');
    const liveChatCTACloseBtn = document.getElementById('liveChatCTACloseBtn');
    const liveChatCTASkipBtn = document.getElementById('liveChatCTASkipBtn');

    if (chatInputContainer && liveChatCTAModal) {
        let hoverTimeout;

        // Function to show live chat CTA modal
        function showLiveChatCTAModal() {
            liveChatCTAModal.classList.add('active');
        }

        // Function to hide live chat CTA modal
        function hideLiveChatCTAModal() {
            liveChatCTAModal.classList.remove('active');
        }

        // Add hover effect with class and show modal after delay
        chatInputContainer.addEventListener('mouseenter', () => {
            chatInputContainer.classList.add('cta-login');

            // Show modal after 500ms hover
            hoverTimeout = setTimeout(() => {
                showLiveChatCTAModal();
            }, 500);
        });

        chatInputContainer.addEventListener('mouseleave', () => {
            chatInputContainer.classList.remove('cta-login');

            // Clear hover timeout if user leaves before delay
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
            }
        });

        // Show modal immediately on click
        chatInputContainer.addEventListener('click', (e) => {
            e.preventDefault();

            // Clear hover timeout
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
            }

            showLiveChatCTAModal();
        });

        // Close modal on close button click
        if (liveChatCTACloseBtn) {
            liveChatCTACloseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                hideLiveChatCTAModal();
            });
        }

        // Close modal on skip button click
        if (liveChatCTASkipBtn) {
            liveChatCTASkipBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                hideLiveChatCTAModal();
            });
        }

        // Close modal on backdrop click
        const liveChatCTABackdrop = liveChatCTAModal.querySelector('.live-chat-cta-backdrop');
        if (liveChatCTABackdrop) {
            liveChatCTABackdrop.addEventListener('click', (e) => {
                if (e.target === liveChatCTABackdrop) {
                    hideLiveChatCTAModal();
                }
            });
        }
    }
})();