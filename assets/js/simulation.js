/**
 * Chat Simulation Logic for maxpreps.news
 * Extracted from index.php for performance optimization.
 */
(function() {
    // These variables should be passed from the PHP side or set globally
    const homeTeamName = window.eventConfig?.homeTeamName || 'Home Team';
    const awayTeamName = window.eventConfig?.awayTeamName || 'Away Team';
    const isEventStarted = window.eventConfig?.isEventStarted || false;
    const eventDateTime = window.eventConfig?.eventDateTime || { date: '', time: '', fullDate: '' };
    const locationText = window.eventConfig?.locationText || '';
    
    const chatMessages = document.getElementById('chatMessages');
    const chatViewerCount = document.getElementById('chatViewerCount');

    if (!chatMessages) return;

    // Realistic names
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

    const socialAvatars = {
        facebook: `<svg viewBox="0 0 24 24" fill="#1877F2" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>`,
        telegram: `<svg viewBox="0 0 24 24" fill="#0088cc" xmlns="http://www.w3.org/2000/svg"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>`,
        twitter: `<svg viewBox="0 0 24 24" fill="#000000" xmlns="http://www.w3.org/2000/svg"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>`
    };

    const userAvatars = {};

    function getRandomAuthor() {
        return realisticNames[Math.floor(Math.random() * realisticNames.length)];
    }

    function getUserAvatar(author) {
        if (!userAvatars[author]) {
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

    const qaThreadsCountdown = [];
    const qaThreadsGeneral = [];
    
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
    
    const qaThreads = [...qaThreadsCountdown, ...qaThreadsGeneral];

    const gameMessages = isEventStarted ? [
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
    let questionAuthor = null;
    let lastQuestionTime = 0;
    const minQuestionInterval = 35000;
    
    const messageCooldown = 180000;
    const usedMessages = new Map();
    
    const qaQuestionCooldown = 300000;
    const usedQaQuestions = new Map();

    function getAvailableMessages(messages) {
        const now = Date.now();
        return messages.filter(msg => {
            const lastUsed = usedMessages.get(msg);
            if (!lastUsed) return true;
            return (now - lastUsed) >= messageCooldown;
        });
    }
    
    function getAvailableThreads(threads) {
        const now = Date.now();
        return threads.filter(thread => {
            const threadKey = thread.messages.map(m => m.text).join('|');
            const lastUsed = usedMessages.get(threadKey);
            if (!lastUsed) return true;
            return (now - lastUsed) >= messageCooldown;
        });
    }
    
    function getAvailableQaQuestions(qaThreads) {
        const now = Date.now();
        return qaThreads.filter(qaThread => {
            const questionText = qaThread.question;
            const lastUsed = usedQaQuestions.get(questionText);
            if (!lastUsed) return true;
            return (now - lastUsed) >= qaQuestionCooldown;
        });
    }

    function checkEventStatus() {
        const countdownTimer = document.getElementById('countdownTimer');
        const countdownVisible = countdownTimer && 
                                countdownTimer.style.display !== 'none' && 
                                window.getComputedStyle(countdownTimer).display !== 'none';
        
        return !countdownVisible;
    }

    function shouldShowQA() {
        const videoOverlay = document.querySelector('.video-player__overlay');
        const countdownTimer = document.getElementById('countdownTimer');
        const videoCtaOverlay = document.querySelector('.video-player__cta-overlay');
        
        const countdownVisible = countdownTimer && 
                                countdownTimer.style.display !== 'none' && 
                                window.getComputedStyle(countdownTimer).display !== 'none';
                                
        const playBtnVisible = videoOverlay && 
                            !videoOverlay.classList.contains('hidden');
                            
        const ctaVisible = videoCtaOverlay && 
                        videoCtaOverlay.classList.contains('active');
        
        return countdownVisible || playBtnVisible || ctaVisible;
    }

    function getContextualMessage() {
        const currentIsStarted = checkEventStatus();
        const activeThreads = conversationThreads;
        const activeMessages = gameMessages;
        
        const showQA = shouldShowQA();
        const now = Date.now();
        const timeSinceLastQuestion = now - lastQuestionTime;
        const shouldForceQuestion = timeSinceLastQuestion >= minQuestionInterval;
        
        const questionChance = shouldForceQuestion ? 1.0 : 0.45;
        
        if (showQA && !qaActive && Math.random() < questionChance) {
            const countdownTimer = document.getElementById('countdownTimer');
            const countdownVisible = countdownTimer && 
                                    countdownTimer.style.display !== 'none' && 
                                    window.getComputedStyle(countdownTimer).display !== 'none';
            
            const allQaThreads = countdownVisible ? qaThreadsCountdown : qaThreadsGeneral;
            const availableQaThreads = getAvailableQaQuestions(allQaThreads);
            const threadsToUse = availableQaThreads.length > 0 ? availableQaThreads : allQaThreads;
            
            if (threadsToUse.length > 0) {
                const qaIndex = Math.floor(Math.random() * threadsToUse.length);
                currentQAThread = threadsToUse[qaIndex];
                qaAnswerIndex = 0;
                qaActive = true;
                conversationActive = false;
                lastQuestionTime = now;
                usedQaQuestions.set(currentQAThread.question, now);
                return { type: 'question', text: currentQAThread.question };
            }
        }
        
        if (Math.random() < 0.4 && activeThreads.length > 0) {
            const availableThreads = getAvailableThreads(activeThreads);
            const threadsToUse = availableThreads.length > 0 ? availableThreads : activeThreads;
            
            const thread = threadsToUse[currentThreadIndex % threadsToUse.length];
            if (threadMessageIndex < thread.messages.length) {
                const message = thread.messages[threadMessageIndex];
                threadMessageIndex++;
                conversationActive = true;
                return { type: 'message', text: message.text };
            } else {
                currentThreadIndex = (currentThreadIndex + 1) % threadsToUse.length;
                threadMessageIndex = 0;
                conversationActive = false;
            }
        }
        
        if (showQA && !qaActive) {
            const timeRatio = timeSinceLastQuestion / minQuestionInterval;
            const questionChance = Math.min(0.6, 0.3 + (timeRatio * 0.3));
            
            if (Math.random() < questionChance) {
                const countdownTimer = document.getElementById('countdownTimer');
                const countdownVisible = countdownTimer && 
                                        countdownTimer.style.display !== 'none' && 
                                        window.getComputedStyle(countdownTimer).display !== 'none';
                
                const allQaThreads = countdownVisible ? qaThreadsCountdown : qaThreadsGeneral;
                const availableQaThreads = getAvailableQaQuestions(allQaThreads);
                const threadsToUse = availableQaThreads.length > 0 ? availableQaThreads : allQaThreads;
                
                if (threadsToUse.length > 0) {
                    const qaIndex = Math.floor(Math.random() * threadsToUse.length);
                    currentQAThread = threadsToUse[qaIndex];
                    qaAnswerIndex = 0;
                    qaActive = true;
                    conversationActive = false;
                    lastQuestionTime = now;
                    usedQaQuestions.set(currentQAThread.question, now);
                    return { type: 'question', text: currentQAThread.question };
                }
            }
        }
        
        conversationActive = false;
        const availableMessages = getAvailableMessages(activeMessages);
        const messagesToUse = availableMessages.length > 0 ? availableMessages : activeMessages;
        const selectedMessage = messagesToUse[Math.floor(Math.random() * messagesToUse.length)];
        return { type: 'message', text: selectedMessage };
    }

    function getNextAuthor() {
        if (conversationActive && lastAuthor && Math.random() < 0.4) {
            return lastAuthor;
        }
        lastAuthor = getRandomAuthor();
        return lastAuthor;
    }

    function showTypingIndicator(author) {
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
    
    function removeTypingIndicator(author) {
        const typingIndicator = chatMessages.querySelector(`.live-chat-typing[data-author="${author}"]`);
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    function addChatMessage(author, text, color, showTyping = true) {
        const typingDelay = showTyping ? Math.min(3000, Math.max(800, text.length * 80)) : 0;
        
        let typingIndicator = null;
        if (showTyping && typingDelay > 0) {
            typingIndicator = showTypingIndicator(author);
        }
        
        setTimeout(() => {
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
            
            const nowTimestamp = Date.now();
            usedMessages.set(text, nowTimestamp);
            
            if (Math.random() < 0.1) {
                const cleanupTime = nowTimestamp - messageCooldown;
                for (const [msg, timestamp] of usedMessages.entries()) {
                    if (timestamp < cleanupTime) {
                        usedMessages.delete(msg);
                    }
                }
                
                const qaCleanupTime = nowTimestamp - qaQuestionCooldown;
                for (const [question, timestamp] of usedQaQuestions.entries()) {
                    if (timestamp < qaCleanupTime) {
                        usedQaQuestions.delete(question);
                    }
                }
            }
            
            const messages = chatMessages.querySelectorAll('.live-chat-message');
            if (messages.length > 20) {
                messages[0].remove();
            }
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, typingDelay);
    }

    function updateViewerCount() {
        if (!chatViewerCount) return;
        const currentText = chatViewerCount.textContent || '250 watching';
        const currentCount = parseInt(currentText.replace(/[^0-9]/g, '')) || 250;
        
        const randomChange = Math.floor(Math.random() * 41) - 20;
        const newCount = Math.max(85, Math.min(500, currentCount + randomChange));
        chatViewerCount.textContent = newCount + ' watching';
    }

    setTimeout(() => {
        let qaWasShown = false;
        
        const qandaStatus = shouldShowQA();
        if (qandaStatus) {
            try {
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
                        const firstQuestionAuthor = getNextAuthor();
                        const now = Date.now();
                        lastQuestionTime = now;
                        usedQaQuestions.set(currentQAThread.question, now);
                        addChatMessage(firstQuestionAuthor, currentQAThread.question);
                        
                        const firstAnswerDelay = 3000 + Math.random() * 2000;
                        const storedQuestionAuthor = firstQuestionAuthor;
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
            } catch (e) {
                console.error('Error in initial Q&A:', e);
            }
        }
        
        const normalMessageStartDelay = qaWasShown ? 6000 : 1500;
        const numNormalMessages = qaWasShown ? 4 : 5;
        
        for (let i = 0; i < numNormalMessages; i++) {
            setTimeout(() => {
                const author = getNextAuthor();
                const activeMessages = gameMessages;
                
                if (activeMessages && activeMessages.length > 0) {
                    const normalText = activeMessages[Math.floor(Math.random() * activeMessages.length)];
                    addChatMessage(author, normalText, null, true);
                }
            }, normalMessageStartDelay + (i * 800));
        }
    }, 1500);

    function addNextMessage() {
        const showQA = shouldShowQA();
        if (!showQA && qaActive) {
            qaActive = false;
            currentQAThread = null;
            qaAnswerIndex = 0;
            questionAuthor = null;
        }
        
        const author = getNextAuthor();
        const messageResult = getContextualMessage();
        
        if (messageResult.type === 'question' && showQA) {
            questionAuthor = author;
            lastQuestionTime = Date.now();
            addChatMessage(author, messageResult.text);
            
            if (currentQAThread && currentQAThread.answers) {
                const numAnswers = Math.random() < 0.4 ? 1 : (Math.random() < 0.7 ? 2 : 3);
                const selectedAnswers = [];
                
                const shuffled = [...currentQAThread.answers].sort(() => Math.random() - 0.5);
                for (let i = 0; i < numAnswers && i < shuffled.length; i++) {
                    selectedAnswers.push(shuffled[i]);
                }
                
                const maxTotalDelay = 40000;
                const firstAnswerDelay = 3000 + Math.random() * 3000;
                let cumulativeDelay = firstAnswerDelay;
                
                const answeringTargetAuthor = questionAuthor;

                setTimeout(() => {
                    const answerAuthor = getNextAuthor();
                    const answerText = `@${answeringTargetAuthor} ${selectedAnswers[0].text}`;
                    addChatMessage(answerAuthor, answerText);
                }, cumulativeDelay);
                
                for (let i = 1; i < selectedAnswers.length; i++) {
                    const remainingAnswers = selectedAnswers.length - i;
                    const remainingTime = maxTotalDelay - cumulativeDelay;
                    const delayBetweenAnswers = remainingTime / (remainingAnswers + 1);
                    const nextDelay = Math.min(
                        delayBetweenAnswers + (Math.random() * 3000 - 1500),
                        12000
                    );
                    cumulativeDelay += Math.max(6000, nextDelay);
                    
                    if (cumulativeDelay > maxTotalDelay) {
                        cumulativeDelay = maxTotalDelay - (remainingAnswers * 1500);
                    }
                    
                    const nextSelectedAnswer = selectedAnswers[i];
                    setTimeout(() => {
                        const nextAnswerAuthor = getNextAuthor();
                        const nextAnswerText = `@${answeringTargetAuthor} ${nextSelectedAnswer.text}`;
                        addChatMessage(nextAnswerAuthor, nextAnswerText);
                    }, cumulativeDelay);
                }
                
                const lastAnswerDelay = cumulativeDelay;
                setTimeout(() => {
                    qaActive = false;
                    currentQAThread = null;
                    qaAnswerIndex = 0;
                    questionAuthor = null;
                    addNextMessage();
                }, lastAnswerDelay + 4000 + Math.random() * 2000);
            } else {
                setTimeout(() => {
                    qaActive = false;
                    currentQAThread = null;
                    qaAnswerIndex = 0;
                    questionAuthor = null;
                    addNextMessage();
                }, 3000 + Math.random() * 2000);
            }
        } else {
            addChatMessage(author, messageResult.text);
            
            setTimeout(() => {
                addNextMessage();
            }, 2500 + Math.random() * 3000);
        }
    }

    setTimeout(() => {
        addNextMessage();
    }, 5000);

    updateViewerCount();
    setInterval(updateViewerCount, 10000);

    const chatInputContainer = document.querySelector('.live-chat-input-container');
    const liveChatCTAModal = document.getElementById('liveChatCTAModal');
    const liveChatCTACloseBtn = document.getElementById('liveChatCTACloseBtn');
    const liveChatCTASkipBtn = document.getElementById('liveChatCTASkipBtn');
    
    if (chatInputContainer && liveChatCTAModal) {
        let hoverTimeout;
        
        function showLiveChatCTAModal() {
            liveChatCTAModal.classList.add('active');
        }
        
        function hideLiveChatCTAModal() {
            liveChatCTAModal.classList.remove('active');
        }
        
        chatInputContainer.addEventListener('mouseenter', () => {
            chatInputContainer.classList.add('cta-login');
            hoverTimeout = setTimeout(() => {
                showLiveChatCTAModal();
            }, 500);
        });
        
        chatInputContainer.addEventListener('mouseleave', () => {
            chatInputContainer.classList.remove('cta-login');
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
            }
        });
        
        chatInputContainer.addEventListener('click', (e) => {
            e.preventDefault();
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
            }
            showLiveChatCTAModal();
        });
        
        if (liveChatCTACloseBtn) {
            liveChatCTACloseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                hideLiveChatCTAModal();
            });
        }
        
        if (liveChatCTASkipBtn) {
            liveChatCTASkipBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                hideLiveChatCTAModal();
            });
        }
        
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
