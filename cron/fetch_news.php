<?php
/**
 * Optimized News Fetcher with Built-in AI-like Rewriting
 * NO API REQUIRED - Uses advanced PHP algorithms
 * 
 * Workflow:
 * 1. Master content collection (images, articles, videos)
 * 2. Download/scrape all media
 * 3. Smart rewriting using PHP algorithms (100% human-like)
 * 4. Save to server
 * 5. Publish to page
 */

// Include SEO Sports Rewriter for enhanced uniqueness
require_once __DIR__ . '/includes/SEOSportsRewriter.php';

class NewsContentManager {
    private $dataFile;
    private $logFile;
    private $imageDir;
    
    // Multi-sport URL configuration
    private $sportUrls = [];
    
    private $cacheTimeout = 600; // 10 minutes (for testing cronjob every 10 minutes)
    private $maxArticles;
    private $minArticles = 8;
    private $maxArticlesLimit = 12;
    private $dailyLimit = 25;
    private $dailyLimitFile;
    private $rotationFile; // Track which sports have been used
    
    // SEO Sports Rewriter instance
    private $seoRewriter;
    
    // Rewriting templates and vocabularies
    private $titlePrefixes = [
        'Breaking:', 'Latest:', 'Update:', 'Report:', 'Coverage:', 
        'Exclusive:', 'News:', 'Recap:', 'Highlights:', 'Analysis:'
    ];
    
    private $synonyms = [
        'high school' => ['secondary school', 'prep sports', 'varsity ranks'],
        'student' => ['pupil', 'learner', 'scholar', 'enrolled athlete', 'youth learner', 'academic participant'],
        'teacher' => ['educator', 'instructor', 'faculty member'],
        'class' => ['course', 'lesson', 'subject', 'academic session', 'period', 'instructional block'],
        'exam' => ['test', 'assessment', 'evaluation'],
        'good grades' => ['high scores', 'strong results', 'excellent marks', 'academic honors', 'top-tier grades', 'meritorious marks'],
        'smart' => ['intelligent', 'bright', 'talented'],
        'hard-working' => ['diligent', 'dedicated', 'committed', 'industrious', 'tireless', 'assiduous'],
        'improve' => ['enhance', 'develop', 'progress', 'advance', 'bolster', 'augment'],
        'activity' => ['program', 'event', 'session', 'extracurricular', 'engagement', 'pursuit'],
        'club' => ['organization', 'group', 'association', 'society', 'collective', 'guild'],
        'schedule' => ['timetable', 'calendar', 'plan', 'slate', 'itinerary', 'agenda'],
        'rule' => ['regulation', 'guideline', 'policy', 'standard', 'stipulation', 'dictate'],
        'sport' => ['game', 'athletic activity', 'competition', 'discipline', 'athletic pursuit', 'physical contest'],
        'athlete' => ['player', 'sportsman', 'competitor', 'contender', 'playmaker', 'athletic standout'],
        'team' => ['squad', 'program', 'lineup', 'side', 'unit', 'roster', 'contingent', 'collective'],
        'coach' => ['trainer', 'instructor', 'leader', 'strategist'],
        'win' => ['victory', 'triumph', 'success', 'conquest', 'favorable result', 'W', 'triumphant outcome'],
        'lose' => ['defeat', 'setback', 'lapse', 'unsuccessful result', 'loss', 'downfall'],
        'match' => ['game', 'contest', 'fixture', 'showdown', 'clash', 'duel', 'engagement'],
        'tournament' => ['championship', 'league', 'competition', 'finals', 'playoff bracket', 'invitational'],
        'practice' => ['training', 'drill', 'workout', 'rehearsal', 'preparation session', 'daily regime'],
        'strong' => ['powerful', 'fit', 'well-built', 'formidable', 'relentless', 'sturdy'],
        'fast' => ['quick', 'speedy', 'rapid', 'fleet-footed', 'swift', 'snappy'],
        'competitive' => ['challenging', 'intense', 'fierce', 'demanding', 'high-stakes', 'adversarial'],
        'active' => ['energetic', 'dynamic', 'involved', 'vigorous', 'animated'],
        'motivated' => ['driven', 'inspired', 'determined', 'focused', 'ambitious', 'zealous'],
        'successful' => ['accomplished', 'winning', 'high-achieving', 'prosperous', 'fruitful'],
        'professional' => ['skilled', 'disciplined', 'trained', 'polished', 'expert', 'adept'],
        'gym' => ['arena', 'facility', 'performance venue'],
        'basketball' => ['hoops action', 'roundball excursion', 'the sphere and twine', 'hardwood contest'],
        'senior' => ['departing scholar', 'veteran leader', 'final-year athlete'],
        'points' => ['tallies', 'scoring total', 'buckets', 'markers', 'notches', 'conversions', 'points on the board', 'tallies in the box score'],
        'hit' => ['delivered', 'connected on', 'knocked down', 'slotted', 'converted', 'nailed', 'buried', 'drilled'],
        'scored' => ['piled up', 'tallied', 'notched', 'recorded', 'accounted for', 'racked up', 'amassed', 'posted'],
        'broke' => ['eclipsed', 'surpassed', 'shattered', 'overtook', 'reset', 'topped', 'moved past'],
        'record' => ['all-time mark', 'benchmark', 'historic total', 'standard', 'milestone', 'top historic total'],
        'game' => ['contest', 'matchup', 'showdown', 'clash', 'battle', 'engagement', 'tilt', 'fixture'],
        'said' => ['shared', 'commented', 'remarked', 'stated', 'noted', 'expressed', 'detailed', 'affirmed'],
        'great' => ['wonderful', 'special', 'memorable', 'outstanding', 'remarkable', 'significant', 'stellar'],
        'season' => ['campaign', 'winter tenure', 'annual run', 'competitive cycle', 'stretch', 'tenure'],
        'national' => ['country-wide', 'nation-wide', 'all-encompassing', 'top-tier', 'interstate'],
        'career' => ['varsity tenure', 'playing days', 'competitive history', 'four-year run', 'time in uniform'],
        'leader' => ['top spot', 'pinnacle', 'summit', 'number one position', 'apex', 'throne'],
        'average' => ['mean total', 'statistical pace', 'scoring clip', 'output rate', 'mean figures'],
        'shooting' => ['firing', 'launching', 'converting at a clip of', 'connecting at'],
        'field' => ['court', 'hardwood', 'palestra', 'playing surface', 'grounds of play'],
        'defense' => ['protective effort', 'guarding strategy', 'defensive stand', 'back-court pressure'],
        'attack' => ['offensive push', 'scoring drive', 'oncoming pressure', 'forward movement'],
        'victory' => ['win', 'triumph', 'success', 'favorable outcome', 'conquest', 'positive result'],
        'defeat' => ['loss', 'setback', 'lapse', 'unsuccessful result', 'downfall'],
        'history' => ['the archives', 'the record books', 'the history books', 'the ledger'],
        'night' => ['evening', 'after-dark event', 'program', 'nighttime showdown'],
        'minutes' => ['moments', 'ticks of the clock', 'time remaining'],
        'home' => ['local base', 'native grounds', 'hometown soil'],
        // Additional sports-specific synonyms from SEO Rewriter
        'important' => ['crucial', 'vital', 'key', 'significant', 'critical', 'pivotal', 'essential'],
        'big' => ['major', 'significant', 'substantial', 'considerable', 'massive'],
        'best' => ['top', 'premier', 'finest', 'leading', 'elite'],
        'tough' => ['challenging', 'difficult', 'hard-fought', 'intense', 'rigorous'],
        'close' => ['tight', 'narrow', 'hard-fought', 'competitive', 'contested'],
        'quick' => ['fast', 'rapid', 'swift', 'speedy', 'brisk'],
        'currently' => ['presently', 'now', 'at present', 'right now', 'at the moment'],
        'recently' => ['lately', 'of late', 'in recent times', 'as of late'],
        'finally' => ['ultimately', 'eventually', 'at last', 'in the end'],
        'now' => ['currently', 'presently', 'at this point', 'at this juncture'],
        'amazing' => ['spectacular', 'incredible', 'brilliant', 'extraordinary', 'sensational', 'dazzling', 'stunning'],
        'bad' => ['poor', 'weak', 'disappointing', 'lackluster', 'subpar', 'underwhelming'],
        // MASSIVE EXPANSION for 95%+ uniqueness
        'has' => ['possesses', 'holds', 'maintains', 'carries', 'owns', 'features'],
        'was' => ['existed as', 'stood as', 'remained'],
        'were' => ['existed as', 'stood as'],
        'will' => ['shall', 'is set to', 'is expected to', 'is slated to'],
        'would' => ['ought to', 'is likely to', 'should'],
        'make' => ['create', 'produce', 'forge', 'craft', 'generate'],
        'made' => ['created', 'produced', 'forged', 'crafted', 'generated'],
        'get' => ['obtain', 'acquire', 'secure', 'attain', 'procure'],
        'got' => ['obtained', 'acquired', 'secured', 'attained', 'procured'],
        'take' => ['seize', 'grasp', 'capture', 'claim', 'snag'],
        'took' => ['seized', 'grasped', 'captured', 'claimed', 'snagged'],
        'give' => ['provide', 'deliver', 'grant', 'supply', 'offer'],
        'gave' => ['provided', 'delivered', 'granted', 'supplied', 'offered'],
        'put' => ['place', 'position', 'set', 'situate', 'establish'],
        'show' => ['display', 'demonstrate', 'exhibit', 'reveal', 'present'],
        'showed' => ['displayed', 'demonstrated', 'exhibited', 'revealed', 'presented'],
        'find' => ['discover', 'locate', 'uncover', 'identify', 'determine'],
        'found' => ['discovered', 'located', 'uncovered', 'identified', 'determined'],
        'keep' => ['maintain', 'retain', 'preserve', 'sustain', 'uphold'],
        'kept' => ['maintained', 'retained', 'preserved', 'sustained', 'upheld'],
        'see' => ['observe', 'witness', 'view', 'notice', 'perceive'],
        'saw' => ['observed', 'witnessed', 'viewed', 'noticed', 'perceived'],
        'come' => ['arrive', 'approach', 'emerge', 'materialize', 'surface'],
        'came' => ['arrived', 'approached', 'emerged', 'materialized', 'surfaced'],
        'go' => ['proceed', 'advance', 'progress', 'move', 'head'],
        'went' => ['proceeded', 'advanced', 'progressed', 'moved', 'headed'],
        'know' => ['understand', 'comprehend', 'realize', 'recognize', 'grasp'],
        'knew' => ['understood', 'comprehended', 'realized', 'recognized', 'grasped'],
        'think' => ['believe', 'consider', 'feel', 'reckon', 'suppose'],
        'thought' => ['believed', 'considered', 'felt', 'reckoned', 'supposed'],
        'want' => ['desire', 'wish', 'seek', 'aim for', 'aspire to'],
        'wanted' => ['desired', 'wished', 'sought', 'aimed for', 'aspired to'],
        'need' => ['require', 'necessitate', 'demand', 'call for'],
        'needed' => ['required', 'necessitated', 'demanded', 'called for'],
        'try' => ['attempt', 'endeavor', 'strive', 'seek', 'aim'],
        'tried' => ['attempted', 'endeavored', 'strived', 'sought', 'aimed'],
        'work' => ['function', 'operate', 'perform', 'execute'],
        'worked' => ['functioned', 'operated', 'performed', 'executed'],
        'call' => ['name', 'designate', 'refer to as', 'label'],
        'called' => ['named', 'designated', 'referred to as', 'labeled'],
        'use' => ['utilize', 'employ', 'apply', 'leverage', 'deploy'],
        'used' => ['utilized', 'employed', 'applied', 'leveraged', 'deployed'],
        'start' => ['begin', 'commence', 'initiate', 'launch', 'kickoff'],
        'started' => ['began', 'commenced', 'initiated', 'launched', 'kicked off'],
        'turn' => ['rotate', 'pivot', 'shift', 'convert', 'transform'],
        'turned' => ['rotated', 'pivoted', 'shifted', 'converted', 'transformed'],
        'run' => ['operate', 'manage', 'direct', 'oversee', 'execute'],
        'ran' => ['operated', 'managed', 'directed', 'oversaw', 'executed'],
        'move' => ['shift', 'relocate', 'transfer', 'advance', 'progress'],
        'moved' => ['shifted', 'relocated', 'transferred', 'advanced', 'progressed'],
        'set' => ['establish', 'determine', 'fix', 'arrange', 'configure'],
        'feel' => ['sense', 'perceive', 'experience', 'detect'],
        'felt' => ['sensed', 'perceived', 'experienced', 'detected'],
        'bring' => ['deliver', 'transport', 'convey', 'carry', 'introduce'],
        'brought' => ['delivered', 'transported', 'conveyed', 'carried', 'introduced'],
        'become' => ['evolve into', 'develop into', 'transform into', 'turn into'],
        'became' => ['evolved into', 'developed into', 'transformed into', 'turned into'],
        'leave' => ['depart', 'exit', 'abandon', 'vacate'],
        'left' => ['departed', 'exited', 'abandoned', 'vacated'],
        'hold' => ['grasp', 'grip', 'maintain', 'retain', 'possess'],
        'held' => ['grasped', 'gripped', 'maintained', 'retained', 'possessed'],
        'meet' => ['encounter', 'confront', 'face', 'engage'],
        'met' => ['encountered', 'confronted', 'faced', 'engaged'],
        'include' => ['incorporate', 'comprise', 'contain', 'encompass'],
        'included' => ['incorporated', 'comprised', 'contained', 'encompassed'],
        'stand' => ['remain', 'stay', 'exist', 'persist'],
        'stood' => ['remained', 'stayed', 'existed', 'persisted'],
        'serve' => ['function as', 'act as', 'work as'],
        'served' => ['functioned as', 'acted as', 'worked as'],
        'appear' => ['seem', 'look', 'emerge', 'surface'],
        'appeared' => ['seemed', 'looked', 'emerged', 'surfaced'],
        'create' => ['generate', 'produce', 'form', 'develop', 'establish'],
        'created' => ['generated', 'produced', 'formed', 'developed', 'established'],
        'reach' => ['achieve', 'attain', 'arrive at', 'hit'],
        'reached' => ['achieved', 'attained', 'arrived at', 'hit'],
        'pass' => ['surpass', 'exceed', 'overtake', 'go beyond'],
        'passed' => ['surpassed', 'exceeded', 'overtook', 'went beyond'],
        'raise' => ['elevate', 'lift', 'increase', 'boost'],
        'raised' => ['elevated', 'lifted', 'increased', 'boosted'],
        // OBSCURE BUT SENSIBLE SYNONYMS for 95%+ (from analysis)
        'quickly' => ['rapidly', 'swiftly', 'speedily', 'snappily', 'promptly'],
        'elementary' => ['primary', 'foundational', 'basic'],
        'official' => ['sanctioned', 'authorized', 'verified', 'validated', 'certified'],
        'certainly' => ['surely', 'definitely', 'undoubtedly', 'absolutely'],
        'trajectory' => ['path', 'course', 'line', 'progression', 'arc'],
        'impressive' => ['remarkable', 'notable', 'striking', 'outstanding', 'exceptional'],
        'minute' => ['moment', 'instant', 'second', 'brief period'],
        'average' => ['mean', 'median', 'normal', 'typical', 'standard'],
        'triple' => ['three-pointer', 'trey', 'triplet', 'three-point shot'],
        'triples' => ['threes', 'treys', 'triplets', 'three-pointers'],
        'more' => ['additional', 'extra', 'further', 'greater'],
        'itinerary' => ['schedule', 'agenda', 'calendar', 'diary', 'timetable'],
        'championship' => ['title', 'crown', 'trophy', 'laurel'],
        'championships' => ['titles', 'crowns', 'trophies', 'laurels'],
        'rewarding' => ['satisfying', 'fulfilling', 'gratifying', 'pleasing'],
        'built' => ['constructed', 'created', 'formed', 'erected', 'developed'],
        'grateful' => ['thankful', 'appreciative', 'obliged', 'indebted'],
        'rightful' => ['legitimate', 'proper', 'due', 'just'],
        'impression' => ['impact', 'effect', 'mark', 'print', 'influence'],
        'total' => ['sum', 'aggregate', 'combined', 'overall'],
        'consecutive' => ['successive', 'sequential', 'continuous', 'uninterrupted'],
        'streak' => ['run', 'sequence', 'string', 'band', 'span'],
        'fewer' => ['less', 'smaller', 'reduced'],
        'best' => ['finest', 'top', 'premier', 'optimal', 'supreme', 'leading'],
        'happened' => ['occurred', 'transpired', 'took place', 'came about'],
        'stated' => ['said', 'declared', 'mentioned', 'remarked', 'expressed'],
        'believe' => ['think', 'feel', 'suppose', 'reckon', 'consider'],
        'basically' => ['essentially', 'fundamentally', 'primarily', 'mainly'],
        'whole' => ['entire', 'complete', 'full', 'total'],
        'excitement' => ['enthusiasm', 'eagerness', 'thrill', 'exhilaration'],
        'contest' => ['match', 'game', 'competition', 'fixture', 'bout'],
        'atmosphere' => ['environment', 'ambiance', 'setting', 'mood'],
        'definitely' => ['certainly', 'surely', 'absolutely', 'undoubtedly'],
        'favored' => ['suited', 'benefited', 'advantaged', 'helped'],
        'style' => ['approach', 'manner', 'method', 'technique'],
        'heading' => ['moving', 'going', 'proceeding', 'advancing'],
        'winter' => ['cold season', 'off-season'],
        'holds' => ['possesses', 'maintains', 'retains', 'keeps'],
        'single' => ['individual', 'sole', 'lone', 'solitary'],
        'also' => ['additionally', 'furthermore', 'moreover', 'likewise'],
        'nearby' => ['close', 'near', 'adjacent', 'proximate'],
        'another' => ['an additional', 'one more', 'a further'],
        'deep' => ['extensive', 'profound', 'far-reaching'],
        'defend' => ['protect', 'preserve', 'maintain', 'uphold'],
        'section' => ['division', 'segment', 'region', 'area'],
        'performance' => ['showing', 'display', 'execution', 'output'],
        'aligns' => ['matches', 'corresponds', 'fits', 'agrees'],
        'recent' => ['latest', 'new', 'current', 'fresh'],
        'remarkable' => ['notable', 'exceptional', 'outstanding', 'striking'],
        'brought' => ['delivered', 'produced', 'yielded', 'generated'],
        'moving' => ['shifting', 'transitioning', 'advancing', 'progressing'],
        'past' => ['beyond', 'by', 'over', 'once'],
        'needing' => ['requiring', 'demanding', 'necessitating'],
        'break' => ['surpass', 'exceed', 'eclipse', 'shatter'],
        'first' => ['initial', 'opening', 'primary', 'inaugural'],
        'quarter' => ['period', 'frame', 'segment', 'section'],
        'mark' => ['point', 'juncture', 'stage', 'moment'],
        'entering' => ['beginning', 'starting', 'commencing'],
        'scoring' => ['point-producing', 'offensive', 'tallying'],
        'leading' => ['heading', 'topping', 'front-running'],
        'country' => ['nation', 'state', 'land'],
        'per' => ['for each', 'every', 'each'],
        'remaining' => ['left', 'outstanding', 'pending'],
        'regular' => ['standard', 'normal', 'typical', 'usual'],
        'look' => ['aim', 'seek', 'plan', 'intend'],
        'court' => ['floor', 'surface', 'hardwood', 'arena'],
        'person' => ['individual', 'athlete', 'competitor'],
        'memory' => ['recollection', 'remembrance', 'memento'],
        'career' => ['tenure', 'run', 'span', 'time', 'journey'],
        'four-year' => ['quadrennial', 'four-season', 'complete'],
        'debate' => ['discussion', 'argument', 'dispute', 'controversy'],
        'persists' => ['continues', 'endures', 'remains', 'lingers'],
        'holder' => ['owner', 'possessor', 'keeper'],
        'over' => ['across', 'during', 'throughout', 'spanning'],
        'began' => ['started', 'commenced', 'initiated', 'launched'],
        'third' => ['3rd', 'tertiary'],
        'grade' => ['level', 'year', 'class'],
        'senior' => ['final-year', 'graduating', 'fourth-year', 'upperclass'],
        'between' => ['from', 'spanning', 'across'],
        'extended' => ['stretched', 'continued', 'prolonged', 'expanded'],
        'dating' => ['going', 'reaching', 'extending'],
        'back' => ['backward', 'prior', 'earlier'],
        'end' => ['conclusion', 'finale', 'close', 'finish'],
        'hit' => ['made', 'connected', 'sank', 'drained', 'converted'],
        'any' => ['a single', 'one'],
        'shooting' => ['converting', 'making', 'hitting'],
        'percent' => ['percentage', 'rate'],
        'beyond' => ['past', 'outside', 'from behind'],
        'arc' => ['line', 'perimeter', 'distance'],
        'little' => ['small', 'slight', 'bit', 'somewhat'],
        'bit' => ['portion', 'piece', 'part', 'degree'],
        'interview' => ['conversation', 'discussion', 'chat', 'talk'],
        'narrative' => ['story', 'account', 'tale'],
        'just' => ['simply', 'merely', 'only'],
        'always' => ['consistently', 'constantly', 'perpetually'],
        'night' => ['evening', 'nighttime'],
        'during' => ['throughout', 'in', 'within', 'amid'],
        'finishing' => ['ending', 'concluding', 'completing'],
        'points' => ['tallies', 'markers', 'scores', 'buckets'],
    ];

    private $phraseReplacements = [
        'according to sources' => ['as stated by observers', 'per reports', 'as noted by those present', 'according to documentation'],
        'told maxpreps' => ['commented to reporters', 'stated in an interview', 'mentioned during the discussion', 'shared with the media'],
        'it was great' => ['it felt wonderful', 'it was a special moment', 'it was rewarding', 'it was a significant experience'],
        'in this gym' => ['at this venue', 'in this facility', 'on this court', 'within these walls'],
        'all-time best' => ['greatest ever', 'standard-setting mark', 'pinnacle of achievement', 'top historic total'],
        'is becoming' => ['is turning into', 'is evolving as', 'is emerging as', 'is growing into'],
        'looking to defend' => ['aiming to retain', 'hoping to keep', 'seeking back-to-back', 'trying to protect', 'fighting to uphold'],
        'within reach' => ['obtainable', 'possible to achieve', 'nearby', 'attainable', 'within grasp'],
        'subject to verification' => ['pending official review', 'to be confirmed officially', 'awaiting final audit', 'not yet finalized'],
        'in front of' => ['before the eyes of', 'amidst the presence of', 'watching by', 'surrounded by', 'witnessed by'],
        'remains on whether' => ['continues regarding if', 'persists about how', 'is still active concerning whether', 'is a point of contention if'],
        'was all smiles' => ['showed great joy', 'appeared delighted', 'was visibly happy', 'expressed excitement', 'glowed with pride'],
        'improve performance' => ['boost performance', 'enhance ability', 'raise the bar', 'elevate performance', 'sharpen skills'],
        'participate in' => ['engage in', 'take part in', 'be involved with', 'compete within'],
        'physical fitness' => ['overall wellness', 'athletic condition', 'physical health', 'peak conditioning'],
        'Elementary school' => ['primary school', 'foundational education center'],
        'High school' => ['secondary institution', 'varsity ranks', 'senior campus'],
        'Winning' => ['triumphant', 'conquering', 'victorious', 'heralded', 'winning-effort'],
        'Strong' => ['stalwart', 'formidable', 'relentless', 'sturdy', 'robust'],
        'Arena' => ['coliseum', 'palestra', 'amphitheater', 'athletic theater', 'arena of play'],
        'Official' => ['verified', 'authorized', 'sanctioned', 'proctored', 'validated'],
        'first quarter' => ['opening frame', 'initial period', 'start of play'],
        'second quarter' => ['next segment', 'following frame', 'second period'],
        'third quarter' => ['penultimate frame', 'third period'],
        'fourth quarter' => ['final frame', 'closing period', 'the stretch run'],
        'regular season' => ['scheduled campaign', 'standard slate', 'qualifying round'],
        'committed to' => ['is a commit for', 'is heading to', 'will join the ranks of'],
        'breaking the national record' => ['eclipsing the country-wide mark', 'setting a new nationwide standard', 'shattering the top historic total'],
        'varsity career' => ['four-year tenure', 'time at the top level', 'playing history'],
        'averaged 40 points' => ['held a 40-point clip', 'tallied scores at a 40-point rate'],
        'national career leader' => ['all-time benchmark at the country level', 'top historic figure nationwide'],
        'become national career leader' => ['rise to the top of the countrys history books', 'move into the lead on the national stage'],
        'surpassing Aaliyah Chavez' => ['moving past the mark set by Aaliyah Chavez', 'eclipsing the total previously held by Aaliyah Chavez'],
        'national record for 3-pointers' => ['top country-wide total from beyond the arc', 'all-time benchmark for triples'],
        'four-year record' => ['tenure mark', 'four-year milestone', 'quadrennial standard'],
        'hit seven triples' => ['connected on seven from deep', 'converted seven from downtown'],
        'scoring average was fifth' => ['scoring clip ranked in the top five', 'output mean was positioned at number five'],
        // Enhanced game-specific phrases
        'the final score was' => ['the contest concluded with a', 'when the final buzzer sounded, the scoreboard read', 'the matchup ended'],
        'scored NUMBER points' => ['tallied NUMBER points', 'posted NUMBER points', 'contributed NUMBER points', 'recorded NUMBER points'],
        'played great' => ['delivered a strong performance', 'showcased excellence', 'performed at a high level', 'executed brilliantly'],
        'was happy' => ['expressed satisfaction', 'showed pleasure', 'was pleased', 'demonstrated contentment'],
    ];

    private $internalLinks = [
        'High School Basketball' => '/',
        'California Basketball' => '/?state=CA',
        'Texas Basketball' => '/?state=TX',
        'Florida Basketball' => '/?state=FL',
        'National Rankings' => '/?type=rankings',
        'Varsity Sports' => '/'
    ];
    
    private $sentenceStarters = [
        'In an impressive display,',
        'The competition saw',
        'After careful observation,',
        'Sources report that',
        'Taking a look at the latest performance,',
        'Fans and analysts noted that',
        'The recent competition featured',
        'In a significant development,',
        'Observers witnessed how',
        'Reflecting on the recent outcome,'
    ];
    
    public function __construct() {
        $baseDir = dirname(__DIR__);
        $config = require $baseDir . '/config.php';
        
        $maxPrepsBase = rtrim($config['maxpreps_base_url'] ?? 'https://www.maxpreps.com', '/');
        $yahooBase = rtrim($config['yahoo_base_url'] ?? 'https://sports.yahoo.com', '/');

        $this->sportUrls = [
            'boys_football' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,football",
            'boys_basketball' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,basketball",
            'girls_basketball' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,basketball",
            'boys_baseball' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,baseball",
            'girls_softball' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,softball",
            'girls_volleyball' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,volleyball",
            'boys_volleyball' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,volleyball",
            'boys_soccer' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,soccer",
            'girls_soccer' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,soccer",
            'boys_wrestling' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,wrestling",
            'girls_wrestling' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,wrestling",
            'boys_lacrosse' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,lacrosse",
            'girls_lacrosse' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,lacrosse",
            'boys_ice_hockey' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,ice_hockey",
            'girls_field_hockey' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,field_hockey",
            'boys_water_polo' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,water_polo",
            'girls_water_polo' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,water_polo",
            'boys_tennis' => "$maxPrepsBase/news/articles_list.aspx?gendersport=boys,tennis",
            'girls_tennis' => "$maxPrepsBase/news/articles_list.aspx?gendersport=girls,tennis",
            'yahoo_nfl' => "$yahooBase/nfl/",
            'yahoo_nba' => "$yahooBase/nba/",
            'yahoo_mlb' => "$yahooBase/mlb/",
            'yahoo_ncaab' => "$yahooBase/college-basketball/",
        ];

        $this->dataFile = $baseDir . '/data/news.json';
        $this->logFile = $baseDir . '/fetch_debug.log';
        $this->imageDir = $baseDir . '/assets/news_images/';
        
        // Random number of articles between 5-10 per daily run
        $this->maxArticles = rand($this->minArticles, $this->maxArticlesLimit);

        $this->dailyLimitFile = dirname($this->dataFile) . '/daily_quota.json';
        $this->rotationFile = dirname($this->dataFile) . '/sport_rotation.json';
        
        // Initialize SEO Sports Rewriter
        $this->seoRewriter = new SEOSportsRewriter();
        
        $this->ensureDirectories();
    }
    
    private function ensureDirectories() {
        $dirs = [
            dirname($this->dataFile),
            dirname($this->logFile),
            $this->imageDir,
            __DIR__ . '/debug',
            __DIR__ . '/public'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    /**
     * Get rotation data - tracks which sports have been used
     */
    private function getRotationData() {
        if (!file_exists($this->rotationFile)) {
            return ['date' => date('Y-m-d'), 'used_sports' => []];
        }
        
        $data = json_decode(file_get_contents($this->rotationFile), true);
        
        // Reset if it's a new day
        if (($data['date'] ?? '') !== date('Y-m-d')) {
            return ['date' => date('Y-m-d'), 'used_sports' => []];
        }
        
        return $data;
    }
    
    /**
     * Save rotation data
     */
    private function saveRotationData($data) {
        file_put_contents($this->rotationFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Select sport URLs to fetch from - rotates through different sports
     * Returns 2-3 random sports that haven't been used recently
     */
    private function selectSportsToFetch() {
        $rotationData = $this->getRotationData();
        $usedSports = $rotationData['used_sports'] ?? [];
        
        // Get available sports (not used yet today)
        $availableSports = array_diff(array_keys($this->sportUrls), $usedSports);
        
        // If all sports have been used, reset the rotation
        if (empty($availableSports)) {
            $this->log("All sports used today - resetting rotation");
            $availableSports = array_keys($this->sportUrls);
            $usedSports = [];
        }
        
        // Select 2-3 random sports to diversify content
        $numSportsToFetch = rand(2, 3);
        $selectedSports = [];
        
        if (count($availableSports) <= $numSportsToFetch) {
            $selectedSports = $availableSports;
        } else {
            $shuffled = $availableSports;
            shuffle($shuffled);
            $selectedSports = array_slice($shuffled, 0, $numSportsToFetch);
        }
        
        // Update used sports list
        $rotationData['used_sports'] = array_merge($usedSports, $selectedSports);
        $this->saveRotationData($rotationData);
        
        $this->log("Selected sports for this run: " . implode(', ', $selectedSports));
        
        return $selectedSports;
    }
    
    /**
     * Convert sport key to human-readable category name
     */
    private function getSportDisplayName($sportKey) {
        $names = [
            'boys_football' => 'Boys Football',
            'boys_basketball' => 'Boys Basketball',
            'girls_basketball' => 'Girls Basketball',
            'boys_baseball' => 'Boys Baseball',
            'girls_softball' => 'Girls Softball',
            'girls_volleyball' => 'Girls Volleyball',
            'boys_volleyball' => 'Boys Volleyball',
            'boys_soccer' => 'Boys Soccer',
            'girls_soccer' => 'Girls Soccer',
            'boys_wrestling' => 'Boys Wrestling',
            'girls_wrestling' => 'Girls Wrestling',
            'boys_lacrosse' => 'Boys Lacrosse',
            'girls_lacrosse' => 'Girls Lacrosse',
            'boys_ice_hockey' => 'Boys Ice Hockey',
            'girls_field_hockey' => 'Girls Field Hockey',
            'boys_water_polo' => 'Boys Water Polo',
            'girls_water_polo' => 'Girls Water Polo',
            'boys_tennis' => 'Boys Tennis',
            'girls_tennis' => 'Girls Tennis',
            'yahoo_nfl' => 'NFL News',
            'yahoo_nba' => 'NBA News',
            'yahoo_mlb' => 'MLB News',
            'yahoo_ncaab' => 'College Basketball',
        ];
        
        return $names[$sportKey] ?? ucwords(str_replace('_', ' ', $sportKey));
    }
    
    
    /**
     * STEP 1: Master Content Collection
     */
    public function collectMasterContent() {
        $this->log("=== STEP 1: Starting Master Content Collection ===");
        
        if ($this->shouldUseCache()) {
            $this->log("Using cached data");
            return json_decode(file_get_contents($this->dataFile), true);
        }
        
        // Select sports to fetch from (2-3 different sports for diversity)
        $selectedSports = $this->selectSportsToFetch();
        
        $allArticles = [];
        
        // Fetch articles from each selected sport
        foreach ($selectedSports as $sportKey) {
            $sportUrl = $this->sportUrls[$sportKey];
            $sportName = $this->getSportDisplayName($sportKey);
            
            $this->log("Fetching from: $sportName ($sportUrl)");
            
            $html = $this->fetchUrl($sportUrl);
            if (empty($html)) {
                $this->log("WARNING: Failed to fetch $sportName URL");
                continue;
            }
            
            // Save debug HTML
            file_put_contents(__DIR__ . "/debug/list_page_{$sportKey}.html", $html);
            
            // Parse articles and add sport category
            $articles = $this->parseArticleList($html, $sportKey, $sportName);
            
            if (!empty($articles)) {
                $this->log("Found " . count($articles) . " articles from $sportName");
                $allArticles = array_merge($allArticles, $articles);
            }
        }
        
        $this->log("Total articles from all sports: " . count($allArticles));
        
        return $allArticles;
    }
    
    /**
     * STEP 2: Download and Scrape Content
     */
    public function downloadAndScrapeContent($articles) {
        $this->log("=== STEP 2: Starting Download & Scrape ===");
        
        // OPTIMIZATION: Load existing keys to skip duplicate processing
        $existingKeys = [];
        if (file_exists($this->dataFile)) {
            $data = json_decode(file_get_contents($this->dataFile), true);
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (isset($item['key'])) {
                        $existingKeys[$item['key']] = true;
                    }
                }
            }
        }
        $this->log("Found " . count($existingKeys) . " existing articles in database.");

        $enrichedArticles = [];
        $processedCount = 0;
        
        foreach ($articles as $index => $article) {
            // Check if we hit the limit of NEW articles to process
            if ($processedCount >= $this->maxArticles) break;
            
            // SKIP if already exists
            if (isset($existingKeys[$article['key']])) {
                $this->log("SKIPPING existing article: {$article['title']}");
                continue;
            }

            $this->log("Processing article " . ($index + 1) . ": {$article['title']}");
            $processedCount++;
            
            try {
                $articleHtml = $this->fetchUrl($article['url']);
                if (!$articleHtml) {
                    $this->log("WARNING: Failed to fetch article: {$article['url']}");
                    continue;
                }
                
                file_put_contents(__DIR__ . "/debug/article_{$article['key']}.html", $articleHtml);
                
                $enriched = $this->extractArticleContent($article, $articleHtml);
                
                // Download hero image
                $enriched['hero_image'] = $this->downloadImage(
                    $enriched['hero_image_url'], 
                    $article['key'] . '_hero'
                );
                
                // Download images from body
                $enriched['content'] = $this->processBodyImages($enriched['content'], $article['key']);
                
                // Extract videos
                $enriched['videos'] = $this->extractVideos($articleHtml, $article['key']);
                
                $enrichedArticles[] = $enriched;
            } catch (Throwable $e) {
                $this->log("ERROR processing article {$article['title']}: " . $e->getMessage());
            }

            usleep(300000); // 300ms delay
        }
        
        $this->log("Successfully enriched " . count($enrichedArticles) . " articles");
        return $enrichedArticles;
    }
    
    /**
     * STEP 3: Smart Rewriting (NO API NEEDED)
     * Uses advanced algorithms to create human-like, SEO-friendly content
     */
    public function rewriteContent($articles) {
        $this->log("=== STEP 3: Starting Smart Rewriting ===");
        
        $rewrittenArticles = [];
        
        foreach ($articles as $article) {
            $this->log("Rewriting: {$article['title']}");
            
            // Rewrite title
            $rewrittenTitle = $this->rewriteTitle($article['title']);
            
            // Rewrite summary
            $rewrittenSummary = $this->rewriteSummary($article['summary']);
            
            // Determine context: Pro (Yahoo) vs High School (MaxPreps)
            $sportKey = $article['sport_key'] ?? '';
            $context = (strpos($sportKey, 'yahoo_') === 0) ? 'pro' : 'highschool';
            
            // Rewrite main content with context awareness
            $rewrittenContent = $this->rewriteArticle($article['raw_content'], $article['title'], $article['date'], $article['source'], $context);
            
            $article['headline'] = $rewrittenTitle;
            $article['header'] = $rewrittenTitle;
            $article['description'] = $rewrittenSummary;
            $article['content_text'] = $rewrittenContent;
            
            // Calculate and log uniqueness score
            $uniquenessScore = $this->seoRewriter->calculateUniqueness($article['raw_content'], $rewrittenContent);
            $this->log("Uniqueness Score: {$uniquenessScore}%");
            
            // Keep original for reference
            $article['original_title'] = $article['title'];
            $article['original_summary'] = $article['summary'];
            
            // Format HTML content
            $hasVideos = !empty($article['videos']);
            $article['content'] = $this->formatContentAsHTML($rewrittenContent, $article['content'], $hasVideos);
            
            $rewrittenArticles[] = $article;
        }
        
        $this->log("Completed rewriting " . count($rewrittenArticles) . " articles");
        return $rewrittenArticles;
    }
    
    /**
     * Advanced Title Rewriting
     */
    private function rewriteTitle($title) {
        // Remove common patterns and standard news structure (Headline Stripper)
        $title = preg_replace('/^(WATCH:|HIGHLIGHTS:|High school .*?:|Breaking News:)\s*/i', '', $title);
        $title = str_replace([':', ' - ', ' – '], ' ', $title);
        
        $title = trim($title);
        
        // Apply synonym replacement (high probability for Nuclear 4.0)
        $words = explode(' ', $title);
        $newWords = [];
        
        foreach ($words as $word) {
            $lowerWord = strtolower($word);
            
            // TUNED 4.1: 65% probability mutation for words with synonyms (More human)
            if (isset($this->synonyms[$lowerWord]) && rand(1, 100) <= 65) {
                $newWords[] = ucfirst($this->synonyms[$lowerWord][array_rand($this->synonyms[$lowerWord])]);
            } else {
                $newWords[] = $word;
            }
        }
        
        $newTitle = implode(' ', $newWords);
        
        // DISTORTION: 90%+ chance to add noise suffix
        if (rand(1, 100) <= 95) {
            $noiseSuffixes = [' hits headlines', ' takes center stage', ' reported globally', ' making waves', ' observed today', ' creates buzz'];
            $newTitle .= $noiseSuffixes[array_rand($noiseSuffixes)];
        }
        
        return $this->distortFormat($newTitle);
    }
    
    /**
     * Advanced Summary Rewriting
     */
    private function rewriteSummary($summary) {
        if (empty($summary)) return '';
        
        // Add engaging prefix
        $prefixes = [
            'In the latest development,',
            'Recent reports indicate that',
            'According to sources,',
            'The sporting world witnessed',
            'An exciting update reveals that',
            'Breaking news shows that',
            'The competition featured'
        ];
        
        $prefix = $prefixes[array_rand($prefixes)];
        
        // Lowercase first letter of original summary
        $summary = lcfirst($summary);
        
        // Apply word variations
        $summary = $this->applyWordVariations($summary);
        
        return "$prefix $summary";
    }
    
    /**
     * Advanced Article Content Rewriting
     * Creates completely unique, human-like content
     */
    private function rewriteArticle($content, $title = '', $date = '', $sourceLine = '', $context = 'highschool') {
        if (empty($content)) return '';
        
        // CLEANUP: Remove leading video metadata
        $content = preg_replace('/^.*?\d+(,\d+)?\s+views\s+\d+:\d+.*/i', '', $content);
        
        // PRE-PROCESS: Ensure list-like lines and DATE blocks are grouped together by turning 
        // SMART MERGE: Connect list-like items that were separated by newlines, but KEEP them grouped for splitting.
        // We ensure list entries have at least one newline but NOT a double newline between them.
        $content = preg_replace('/(\n\s*\n)(?=\d+.*?[—\-\&]|[-•\*]|(?:[A-Z]{1,3}\s*[—\-\&]))/mi', "\n", $content);

        // Split by double newlines to handle paragraphs
        $paragraphs = explode("\n\n", $content);
        $rewrittenParagraphs = [];
        $isFirstPara = true;
        
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) continue;

             // NUCLEAR OPTION: Regenerate Lead Paragraph with Templates (No AI API)
            if ($isFirstPara && !empty($title)) {
                $isFirstPara = false;
                
                // If the first "paragraph" is very long (splitting failed or just a massive block),
                // only take the first 2-3 sentences for replacement and keep the rest.
                $sentences = preg_split('/(?<=[.!?])\s+/', $para, -1, PREG_SPLIT_NO_EMPTY);
                if (count($sentences) > 3) {
                    $leadSource = implode(' ', array_slice($sentences, 0, 3));
                    $remainder = implode(' ', array_slice($sentences, 3));
                    $rewrittenParagraphs[] = $this->regenerateLeadParagraph($title, $date, $sourceLine, $leadSource, $context);
                    $para = $remainder; // Process character sentences as normal narrative
                    $this->log("PROCEEDING WITH REMAINDER: " . strlen($para) . " chars");
                } else {
                    $rewrittenParagraphs[] = $this->regenerateLeadParagraph($title, $date, $sourceLine, $para, $context);
                    continue;
                }
            }
            $para = trim($para);
            if (empty($para)) continue;
            
            // NUCLEAR 4.0: Split current paragraph into sentences and SHUFFLE them
            // only if they don't seem to have a strict chronological order (simplistic check)
            $paraSentences = preg_split('/(?<=[.!?])\s+/', $para, -1, PREG_SPLIT_NO_EMPTY);
            if (count($paraSentences) > 1 && !preg_match('/\b(first|then|next|finally|after|before|following)\b/i', $para)) {
                shuffle($paraSentences);
                $para = implode(' ', $paraSentences);
            }
            
            // Skip pure metadata
            if (preg_match('/^\d+(,\d+)?\s+views\s+\d+:\d+$/i', $para)) continue;

            // DETECT SCHEDULES/CALENDAR (e.g. Feb. 21: Nevada)
            if (preg_match('/^(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|January|February|March|April|May|June|July|August|September|October|November|December)\.?\s+\d+/mi', $para)) {
                $lines = explode("\n", $para);
                $schedHtml = "<div class=\"schedule-calendar-container\">\n<ul>\n";
                foreach ($lines as $line) {
                    $line = trim($line, " \t\n\r\0\x0B,");
                    if (empty($line)) continue;
                    
                    // Format: [Date Block] [Separator] [Details]
                    // Date block can include multiple numbers and separators like "March 14, 15"
                    if (preg_match('/^((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|January|February|March|April|May|June|July|August|September|October|November|December)\.?\s+[\d,\-\s\x{2013}\x{2014}]+)\s*(?:&nbsp;|nbsp;|[:—\-&])*\s*(.*)$/ui', $line, $matches)) {
                        $date = trim($matches[1]);
                        $details = trim($matches[2], " \t\n\r\0\x0B,;");
                        $schedHtml .= "  <li class=\"schedule-row\">\n";
                        $schedHtml .= "    <span class=\"schedule-date\">$date</span>\n";
                        $schedHtml .= "    <span class=\"schedule-details\">$details</span>\n";
                        $schedHtml .= "  </li>\n";
                    } else {
                        $schedHtml .= "  <li class=\"schedule-meta\">$line</li>\n";
                    }
                }
            $schedHtml .= "</ul>\n</div>";
                $rewrittenParagraphs[] = $schedHtml;
                continue;
            }

            // DETECT LISTS: Multiple lines starting with patterns, or a block containing list patterns
            // We only trigger list mode if the FIRST line matches list pattern, or if it's a known list container
            $isListBlock = preg_match('/^(\d+.*?[—\-&]|[-•\*]|[A-Z]{1,3}\s*[—\-&])/', $para);
            
            if ($isListBlock) {
                $lines = explode("\n", $para);
                $listHtml = "<div class=\"stat-list-container\">\n<ul>\n";
                foreach ($lines as $line) {
                    $line = trim($line, " \t\n\r\0\x0B,");
                    if (empty($line)) continue;

                    // Header detection (Not a list item pattern)
                    if (!preg_match('/^(\d+.*?[—\-&]|[-•\*]|[A-Z]{1,3}\s*[—\-&])/', $line)) {
                        // REWRITE HEADERS TOO to prevent plagiarism leak
                        $rewrittenHeader = $this->rewriteSentence($line);
                        $listHtml .= "  <li class=\"list-header-row\">" . $rewrittenHeader . "</li>\n";
                        continue;
                    }

                    // Item parsing: [Rank/Pos] [Separator] [Name] [Comma] [Detail]
                    // NUCLEAR 6.0: Transform list items into full descriptive sentences
                    if (preg_match('/^(\d+|[A-Z]{1,3})\s*(?:&mdash;|mdash;|[—\-&]|–|—)\s*([^,]+)(?:,\s*(.*))?$/', $line, $matches)) {
                        $rank = $matches[1];
                        $name = trim($matches[2], " \t\n\r\0\x0B,;&");
                        $info = isset($matches[3]) ? trim($matches[3], " \t\n\r\0\x0B,;&") : '';
                        
                        $templates = [
                            "Holding the #$rank spot is $name, who has $info.",
                            "$name occupies position $rank with $info recorded.",
                            "In the $rank slot, $name continues to impress with $info.",
                            "Statistical leaders include $name at $rank, credited with $info."
                        ];
                        $desc = $templates[array_rand($templates)];
                        $listHtml .= "  <li class=\"stat-item descriptive-item\">$desc</li>\n";
                    } 
                    // Roster Format: POS — Name, School
                    elseif (preg_match('/^([A-Z]{1,3})\s*(?:&mdash;|mdash;|[—\-&]|–|—)\s*([^,]+)(?:,\s*(.*))?$/', $line, $matches)) {
                        $pos = $matches[1];
                        $name = trim($matches[2], " \t\n\r\0\x0B,;&");
                        $info = isset($matches[3]) ? trim($matches[3], " \t\n\r\0\x0B,;&") : '';
                        
                        $listHtml .= "  <li class=\"stat-item\">\n";
                        $listHtml .= "    <span class=\"stat-rank\">$pos</span>\n";
                        $listHtml .= "    <span class=\"stat-name\">$name</span>\n";
                        if ($info) $listHtml .= "    <span class=\"stat-info\">$info</span>\n";
                        $listHtml .= "  </li>\n";
                    }
                    // COMPACT LEADERBOARD Format: 1Name - Info Score
                    elseif (preg_match('/^(\d+)(.*?)\s+\-\s+(.*?)(\d+(?:\.\d+)?)$/ui', $line, $matches)) {
                        $rank = $matches[1];
                        $name = trim($matches[2]);
                        $info = trim($matches[3], " \t\n\r\0\x0B,;");
                        $score = $matches[4];
                        
                        $listHtml .= "  <li class=\"stat-item leaderboard-item\">\n";
                        $listHtml .= "    <span class=\"stat-rank\">$rank</span>\n";
                        $listHtml .= "    <span class=\"stat-name\">$name</span>\n";
                        $listHtml .= "    <span class=\"stat-info\">$info</span>\n";
                        $listHtml .= "    <span class=\"stat-score\">$score</span>\n";
                        $listHtml .= "  </li>\n";
                    }
                    else {
                        // Fallback for non-standard formats
                        $listHtml .= "  <li>" . $this->rewriteSentence($line) . "</li>\n";
                    }
                }
                $listHtml .= "</ul>\n</div>";
                $rewrittenParagraphs[] = $listHtml;
                continue;
            }

            
            // DETECT FOOTER/EDITORIAL NOTE
            if (strpos(strtolower($para), 'editorial note') !== false || strpos(strtolower($para), 'this report was') !== false) {
                $rewrittenParagraphs[] = "<div class=\"editorial-note-box\">" . $para . "</div>";
                continue;
            }

            // DETECT HEADERS: Short, capitalized, no period, no common data tags
            $isPotentialHeader = (strlen($para) < 70 && !preg_match('/[.!?:]$/', $para) && preg_match('/^[A-Z]/', $para));
            $hasExcludedWord = preg_match('/\b(Record|School|Years|Year|Leader|Week|Date|vs\.|OF|P|C|1B|2B|3B|SS|DH|UTIL|INF|RHP|LHP)\b/i', $para);
            
            if ($isPotentialHeader && !$hasExcludedWord) {
                $this->log("HEADER DETECTED: $para");
                // For headers, only use word variations, skip context phrases
                $rewrittenParagraphs[] = "<h2>" . $this->applyWordVariations($para) . "</h2>";
                continue;
            }



            
            // NORMAL NARRATIVE: Split into sentences
            $sentences = preg_split('/(?<=[.!?])\s+/', $para, -1, PREG_SPLIT_NO_EMPTY);
            $rewrittenSentences = [];
            
            foreach ($sentences as $sentence) {
                if (strlen($sentence) < 20) {
                    $rewrittenSentences[] = $sentence;
                    continue;
                }
                
                // AGGRESSIVE: Split complex sentences first
                $subSentences = $this->splitComplexSentences($sentence);
                
                foreach ($subSentences as $sub) {
                    // SPECIAL: Restructure record-breaking patterns
                    $sub = $this->restructureRecordBreaking($sub);
                    
                    $newSentence = $this->rewriteSentence($sub);
                    $rewrittenSentences[] = $newSentence;
                }
            }
            
            $finalPara = implode(' ', $rewrittenSentences);
            
            // Apply highlighting (bold) and internal links
            $finalPara = $this->applyHighlights($finalPara);
            $finalPara = $this->insertInternalLinks($finalPara);
            
            $rewrittenParagraphs[] = "<p>$finalPara</p>";
        }
        
        // SHUFFLE: Randomize middle paragraphs safely
        $rewrittenParagraphs = $this->shuffleParagraphsSafe($rewrittenParagraphs);
        
        // ADD: Context-aware Footer
        $rewrittenParagraphs[] = $this->generateUniqueFooter($context);
        
        $finalContent = implode("\n\n", $rewrittenParagraphs);
        
        // POST-PROCESS: Smooth out awkward phrasing
        return $this->smoothContent($finalContent);
    }

    /**
     * AGGRESSIVE REWRITING: Splits long sentences into shorter, distinct ones.
     * Targeted at breaking "and... giving her..." structures.
     */
    private function splitComplexSentences($sentence) {
        $parts = [];
        
        // Pattern 1: "..., giving her [STATS]..." -> ". This brought her total to [STATS]..."
        if (preg_match('/(.*?),\s+giving\s+(?:her|him|them)\s+(.*?)(?:\.|and\s+)(.*)?$/i', $sentence, $matches)) {
            $parts[] = trim($matches[1]) . ".";
            $parts[] = "This performance brought the total to " . trim($matches[2]) . ".";
            if (!empty($matches[3])) $parts[] = ucfirst(trim($matches[3]));
            return $parts;
        }

        // Pattern 2: "..., surpassing [NAME]..." -> ". In doing so, [he/she] overtook [NAME]..."
        if (preg_match('/(.*?),\s+surpassing\s+(.*?)(?:\.|$)/i', $sentence, $matches)) {
            $parts[] = trim($matches[1]) . ".";
            $parts[] = "In doing so, the record of " . trim($matches[2]) . " was eclipsed.";
            return $parts;
        }

        // Pattern 3: Long sentence with "and" + verb (simplistic split)
        // Only if sentence is very long (> 150 chars)
        if (strlen($sentence) > 150 && preg_match('/(.{60,}),\s+and\s+(.{40,})$/', $sentence, $matches)) {
             $parts[] = trim($matches[1]) . ".";
             $parts[] = "Additionally, " . trim($matches[2]);
             return $parts;
        }

        return [$sentence];
    }

    /**
     * SPECIAL: Completely inverts specific record-breaking patterns
     */
    private function restructureRecordBreaking($sentence) {
        // "surpassing [Name] as the leader"
        if (preg_match('/surpassing\s+(.*?)\s+as\s+the\s+(?:all-time\s+)?leader/i', $sentence, $matches)) {
            return "moving past " . $matches[1] . " to take the top spot";
        }
        
        // "breaking the record set by [Name]"
        if (preg_match('/breaking\s+the\s+record\s+set\s+by\s+(.*?)/i', $sentence, $matches)) {
            return "surpassing the previous mark held by " . $matches[1];
        }
        
        return $sentence;
    }

    /**
     * Post-processing to fix awkward phrases AND improve paragraph structure for SEO
     * ENHANCED: Split long paragraphs, add subheadings, clean formatting
     */
    private function smoothContent($content) {
        // STEP 1: Fix awkward phrases and word issues
        $replacements = [
            '/\bscored (\d+) output\b/i' => 'scored $1 points',
            '/\bscored (\d+) tallies\b/i' => 'recorded $1 points',
            '/\bsurpassing (.*?) as the leader\b/i' => 'overtaking $1 for the top spot',
            '/\boutput of (\d+)\b/i' => 'total of $1',
            '/\ba (\d+) output\b/i' => 'a $1-point performance',
            '/\bIn a notable achievement, The\b/i' => 'the',
            '/\bWith impressive skill, The\b/i' => 'the',
            '/\bshowcasing remarkable skill, The\b/i' => 'the',
            '/\bIn a noteworthy performance,\b/i' => 'Notably,',
            '/\bDemonstrating excellence,\b/i' => 'Significantly,',
            '/\bWith impressive skill,\b/i' => 'Impressively,',
            '/\bschedule schedule\b/i' => 'schedule',
            '/\bmatch match\b/i' => 'match',
            '/\b([, ]+),\s+([, ]+),\s+\1/i' => '$1, $2',
            '/\bdo not suppose\b/i' => 'cannot believe',
            '/\bdo not think\b/i' => 'cannot believe',
            '/\bis perpetually\b/i' => 'is always',
            '/\battained to do\b/i' => 'got to do',
            '/\btook place\b/i' => 'happened',
            '/\bin the primary segment\b/i' => 'in the first quarter',
            '/\bthe alternate\b/i' => 'the second quarter',
            '/\bhistoric combined-breaker\b/i' => 'record-breaker',
            '/\bhistoric combined\b/i' => 'record',
            '/\bsupply her\b/i' => 'give her',
            '/\babecedarian school\b/i' => 'elementary school',
            '/\babecedarian academy\b/i' => 'elementary school',
            '/\bprogressed to\b/i' => 'went to',
            '/\b,\s*,/i' => ',',
            '/\.\s*\./i' => '.',
            '/\s+In,\s+(\w+),\s+a noteworthy/i' => ' In a noteworthy',
            '/\s+With impressive skill,\s+With impressive skill,/i' => ' With impressive skill,',
            '/\bWith impressive skill, (remarkably|notably|impressively|significantly),/i' => 'With impressive skill,',
            '/\bIn a notable achievement, (remarkably|notably|impressively|significantly),/i' => 'In a notable achievement,',
            '/\bDemonstrating excellence, (remarkably|notably|impressively|significantly),/i' => 'Demonstrating excellence,',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Fix specific sentence starts that were capitalized incorrectly
        $prefixes = ['Following the latest developments', 'Significantly', 'Notably', 'Impressively', 'Showcasing remarkable talent'];
        foreach ($prefixes as $p) {
            $content = str_replace($p . ', She', $p . ', she', $content);
            $content = str_replace($p . ', He', $p . ', he', $content);
            $content = str_replace($p . ', It', $p . ', it', $content);
            $content = str_replace($p . ', They', $p . ', they', $content);
        }

        
        // Final cleanup for any leftover double spaces or weird punctuation
        $content = str_replace(' ,', ',', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        // STEP 2: Split long paragraphs and add subheadings for SEO
        preg_match_all('/<p>(.*?)<\/p>/s', $content, $matches);
        $paragraphs = $matches[1];
        
        if (!empty($paragraphs) && count($paragraphs) > 0) {
            $cleanedParagraphs = [];
            
            foreach ($paragraphs as $para) {
                $para = trim($para);
                if (empty($para)) continue;
                
                // Split very long paragraphs (> 450 characters)
                if (strlen($para) > 450) {
                    $sentences = preg_split('/(?<=[.!?])\s+/', $para);
                    $currentChunk = '';
                    
                    foreach ($sentences as $sentence) {
                        $sentence = trim($sentence);
                        if (empty($sentence)) continue;
                        
                        if (strlen($currentChunk) > 0 && strlen($currentChunk . ' ' . $sentence) > 350) {
                            $cleanedParagraphs[] = trim($currentChunk);
                            $currentChunk = $sentence;
                        } else {
                            $currentChunk .= (strlen($currentChunk) > 0 ? ' ' : '') . $sentence;
                        }
                    }
                    
                    if (strlen($currentChunk) > 0) {
                        $cleanedParagraphs[] = trim($currentChunk);
                    }
                } else {
                    $cleanedParagraphs[] = $para;
                }
            }
            
            // Add subheading for longer articles (4+ paragraphs)
            if (count($cleanedParagraphs) >= 4) {
                $withSubheadings = [];
                $withSubheadings[] = $cleanedParagraphs[0];
                $withSubheadings[] = $cleanedParagraphs[1];
                
                $subheadings = [
                    '<h2>Performance Highlights</h2>',
                    '<h2>Breaking Down the Numbers</h2>',
                    '<h2>Impact on the Season</h2>',
                    '<h2>Key Takeaways</h2>',
                ];
                $withSubheadings[] = $subheadings[array_rand($subheadings)];
                
                for ($i = 2; $i < count($cleanedParagraphs); $i++) {
                    $withSubheadings[] = $cleanedParagraphs[$i];
                }
                
                $cleanedParagraphs = $withSubheadings;
            }
            
            // Rebuild content with proper tags
            $finalContent = '';
            foreach ($cleanedParagraphs as $para) {
                if (strpos($para, '<h2') === 0) {
                    $finalContent .= $para . "\n\n";
                } else {
                    $finalContent .= '<p>' . $para . '</p>' . "\n\n";
                }
            }
            
            $content = trim($finalContent);
        }
        
        return $content;
    }
    /**
     * Safely shuffle middle paragraphs to break duplication fingerprint
     */
    private function shuffleParagraphsSafe($paragraphs) {
        if (count($paragraphs) < 3) return $paragraphs;
        
        $first = array_shift($paragraphs);
        $last = array_pop($paragraphs);
        
        // Randomly shuffle middle content
        shuffle($paragraphs);
        
        // Occasionally merge two short paragraphs
        if (count($paragraphs) > 2 && rand(1, 10) > 7) {
            $p1 = array_shift($paragraphs);
            $p2 = array_shift($paragraphs);
            array_unshift($paragraphs, $p1 . " " . $p2);
        }
        
        array_unshift($paragraphs, $first);
        array_push($paragraphs, $last);
        
        return $paragraphs;
    }

    /**
     * Generate unique footer content
     */
    private function generateUniqueFooter($context = 'highschool') {
        if ($context === 'pro') {
            $footers = [
                "<p><em>This comprehensive report, synthesized through automated analysis, provides a deep dive into professional sports news.</em></p>",
                "<p><em>Stay tuned for more updates as the pro season reaches its climax. This story continues to evolve.</em></p>",
                "<p><em>For continuous coverage of professional athletics and breaking headlines, keep checking back.</em></p>",
                "<p><em>Analysis provided by our sports desk. Professional statistics are subject to official verification.</em></p>"
            ];
        } else {
            $footers = [
                "<p><em>This article, powered by specialized analysis, provides a unique perspective on the latest high school sports action.</em></p>",
                "<p><em>Stay tuned for more updates as the prep season progresses. Reports indicate this team is one to watch.</em></p>",
                "<p><em>For continuous coverage of high school athletics, keep checking back. The varsity season is just heating up.</em></p>",
                "<p><em>Analysis provided by our sports desk. Varsity stats and records are subject to final verification.</em></p>"
            ];
        }
        
        return $footers[array_rand($footers)];
    }

    /**
     * Intelligently bold key phrases or stats
     */
    private function applyHighlights($text) {
        // Pattern for numbers/stats or proper names
        $patterns = [
            '/\b(\d+ (points|rebounds|assists|threes|3-pointers|yards|touchdowns))\b/i',
            '/\b(breaking|record-breaking|national record|all-time high)\b/i',
            '/\b(committed to|ranked|recruited by)\b/i'
        ];
        
        foreach ($patterns as $pattern) {
            // 30% chance to highlight a match to keep it natural
            $text = preg_replace_callback($pattern, function($matches) {
                return (rand(1, 10) <= 3) ? "<strong>{$matches[0]}</strong>" : $matches[0];
            }, $text);
        }
        
        return $text;
    }

    /**
     * Insert SEO-friendly internal links
     */
    private function insertInternalLinks($text) {
        foreach ($this->internalLinks as $keyword => $url) {
            // Only replace 1 occurrence per paragraph to avoid spamming
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (rand(1, 10) <= 2) { // 20% chance to link
                $text = preg_replace($pattern, "<a href=\"$url\" style=\"color:#CC0000;text-decoration:none;font-weight:600;\">$keyword</a>", $text, 1);
            }
        }
        return $text;
    }
    
    /**
     * Sentence-level rewriting with advanced techniques
     * ENHANCED 95%+: Using 284 synonym groups + contraction expansion
     */
    private function rewriteSentence($sentence) {
        // STRATEGY 0A: CRITICAL - Expand contractions for uniqueness boost
        $sentence = $this->expandContractions($sentence);
        
        // STRATEGY 0B: Quote Paraphrasing (Nuclear 6.0) - KILLER OF PLAGIARISM
        $sentence = $this->paraphraseQuotes($sentence);

        // STRATEGY 1: Fact Extraction & Regeneration (The "Clean Slate" Method)
        $regenerated = $this->regenerateFromFacts($sentence);
        if ($regenerated) {
            return $regenerated;
        }

        // STRATEGY 1.5: Clause Shuffling (Nuclear 6.0) - Forced 80% chance
        if (rand(1, 100) <= 80) {
            $sentence = $this->shuffleClauses($sentence);
        }

        // STRATEGY 2: Synonym replacement (Nuclear 6.0) - FORCED 100%
        $sentence = $this->applyWordVariations($sentence);
        $sentence = $this->scrambleNumbers($sentence);
        $sentence = $this->distortFormat($sentence);
        
        // STRATEGY 2.5: Phrase Level substitution (Forced 100%)
        $sentence = $this->applyPhraseReplacements($sentence);

        // STRATEGY 3: Structure variation (NUCLEAR: 100% CHANCE)
        $sentence = $this->varyStructure($sentence);
        
        // STRATEGY 4: Add context phrases (Forced 60% chance)
        if (rand(1, 100) <= 60) {
            $sentence = $this->addContextPhrase($sentence);
        }
        
        return $sentence;
    }
    
    /**
     * Apply phrase-level replacements
     */
    private function applyPhraseReplacements($text) {
        foreach ($this->phraseReplacements as $phrase => $replacements) {
            $pattern = '/' . preg_quote($phrase, '/') . '/i';
            // Nuclear 6.0: 100% chance for phrase replacement if match found
            $replacement = $replacements[array_rand($replacements)];
            $text = preg_replace($pattern, $replacement, $text);
        }
        return $text;
    }
    
    /**
     * CRITICAL: Expand contractions (KEY for 95%+ uniqueness)
     * Based on analysis of 95% unique example
     * Expands contractions to separate words, boosting uniqueness score
     */
    private function expandContractions($text) {
        $contractions = [
            // Common contractions
            "don't" => "do not",
            "doesn't" => "does not",
            "didn't" => "did not",
            "won't" => "will not",
            "wouldn't" => "would not",
            "can't" => "cannot",
            "couldn't" => "could not",
            "shouldn't" => "should not",
            "isn't" => "is not",
            "aren't" => "are not",
            "wasn't" => "was not",
            "weren't" => "were not",
            "hasn't" => "has not",
            "haven't" => "have not",
            "hadn't" => "had not",
            "I'm" => "I am",
            "you're" => "you are",
            "he's" => "he is",
            "she's" => "she is",
            "it's" => "it is",
            "we're" => "we are",
            "they're" => "they are",
            "I've" => "I have",
            "you've" => "you have",
            "we've" => "we have",
            "they've" => "they have",
            "I'd" => "I would",
            "you'd" => "you would",
            "he'd" => "he would",
            "she'd" => "she would",
            "we'd" => "we would",
            "they'd" => "they would",
            "I'll" => "I will",
            "you'll" => "you will",
            "he'll" => "he will",
            "she'll" => "she will",
            "we'll" => "we will",
            "they'll" => "they will",
            "that's" => "that is",
            "there's" => "there is",
            "here's" => "here is",
            "what's" => "what is",
            "who's" => "who is",
            "where's" => "where is",
            "how's" => "how is",
            "let's" => "let us",
        ];
        
        // Apply expansions
        foreach ($contractions as $contraction => $expansion) {
            // Case-insensitive replacement
            $text = preg_replace('/\b' . preg_quote($contraction, '/') . '\b/i', $expansion, $text);
        }
        
        return $text;
    }

    /**
     * NUCLEAR OPTION: Create a brand new lead paragraph from metadata
     */
    private function regenerateLeadParagraph($title, $date, $source, $originalPara, $context = 'highschool') {
        // remove "WATCH:" etc
        $cleanTitle = preg_replace('/^(WATCH:|HIGHLIGHTS:|High school .*?:|Report:)\s*/i', '', $title);
        $dayName = date('l', strtotime($date));
        
        // Templates based on context
        if ($context === 'pro') {
            $templates = [
                "<p>In the latest professional sports coverage, <strong>$cleanTitle</strong> has emerged as a major focal point. Fans and analysts alike are weighing in on the recent performance.</p>",
                "<p>Headlines were made in the pro ranks on $dayName as <strong>$cleanTitle</strong> dominated the conversation. The fallout from this development is expected to impact the standings significantly.</p>",
                "<p>The sporting world is reacting to reports that <strong>$cleanTitle</strong>. This news comes at a critical juncture in the professional season.</p>",
                "<p>Breaking news from the pro circuit: <strong>$cleanTitle</strong>. This achievement has quickly become one of the most discussed topics of the week.</p>"
            ];
        } else {
            $templates = [
                "<p>In significant high school sports action this week, the spotlight turned to <strong>$cleanTitle</strong>. Local fans witnessed a standout performance that is generating buzz.</p>",
                "<p>Prep sports headlines were made on $dayName as <strong>$cleanTitle</strong> took center stage in the varsity ranks. The latest reports indicate a noteworthy achievement for the program.</p>",
                "<p>The high school athletics community is reacting to the news that <strong>$cleanTitle</strong>. This development comes as teams are pushing for position in the postseason race.</p>",
                "<p>Breaking news from the prep ranks: <strong>$cleanTitle</strong>. This remarkable feat has quickly become a major talking point in high school sports.</p>"
            ];
        }
        
        // 10% chance to just return heavily modified original if it's really short
        if (strlen($originalPara) < 50 && rand(1, 100) <= 10) {
             return "<p>" . $this->varyStructure($this->applyWordVariations($originalPara)) . "</p>";
        }

        return $templates[array_rand($templates)];
    }

    /**
     * FACT EXTRACTION & REGENERATION
     * Extracts rigid data points and builds a new sentence from zero.
     * SAFETY: Using low threshold (0.1) to ensure stats are rewritten even in long sentences.
     */
    private function regenerateFromFacts($sentence) {
        $len = strlen($sentence);
        if ($len < 10) return null;
        
        $templates = [];
        
        // Pattern 1: Multi-stat (Points + Extra Stat)
        if (preg_match('/([A-Z][a-z]+)\s+.*?\b(\d+)\s+(points|markers|tallies|buckets)\s+(?:and|with)\s+(\d+)\s+(rebounds|assists|steals|blocks|threes|3-pointers|triples)\b/i', $sentence, $m)) {
            $name = $m[1];
            $pts = $m[2];
            $ptsUnit = $m[3];
            $stat2 = $m[4];
            $stat2Unit = $m[5];
            
            $templates = [
                "$name delivered a multi-faceted performance, accounting for $pts $ptsUnit while also contributing $stat2 $stat2Unit.",
                "With $pts $ptsUnit and $stat2 $stat2Unit, $name was a focal point for the team's effort.",
                "The scoreboard reflected $name's impact as the standout recorded $pts $ptsUnit alongside $stat2 $stat2Unit.",
                "In a dominant showing, $name pilled up $pts $ptsUnit and added $stat2 $stat2Unit to the tally."
            ];
        }
        // Pattern 2: Record Breaking + surpassing
        elseif (preg_match('/surpassing\s+(.*?)\s+as\s+the\s+(?:all-time\s+)?leader/i', $sentence, $m)) {
            $prev = $m[1];
            $templates = [
                "The historic record previously held by $prev has been eclipsed.",
                "Moving ahead of $prev, a new all-time benchmark was established.",
                "$prev was overtaken at the summit of the history books.",
                "A new chapter was written as the total surpassed the mark set by $prev.",
                "Establishing a new standard, the performance went past the figures once held by $prev."
            ];
        }
        // Pattern 4: Commitment
        elseif (preg_match('/([A-Z][a-z]+(?: [A-Z][a-z]+)?)\s+is\s+a\s+(.*?)\s+commit/i', $sentence, $m)) {
            $name = $m[1];
            $school = $m[2];
            $templates = [
                "Heading to $school, $name is set for the next chapter of a collegiate journey.",
                "The program at $school will welcome $name, who has officially committed.",
                "Expected to join $school, $name has finalized future plans.",
                "As a future representative of $school, $name continues to showcase top-tier talent."
            ];
        }
        // Pattern 5: Scoring Average
        elseif (preg_match('/(\d+\.\d+)\s+scoring\s+average/i', $sentence, $m)) {
            $avg = $m[1];
            $templates = [
                "With a scoring clip of $avg per contest, the output remains elite.",
                "Maintaining a $avg pace on the scoreboard has been a hallmark of the season.",
                "The statistical output stands at a $avg mean for the winter campaign.",
                "A $avg clip per game reflects the consistent scoring threat."
            ];
        }
        // Pattern 3: Single Stat (Points)
        elseif (preg_match('/([A-Z][a-z]+)\s+.*?\b(\d+)\s+(points|markers|tallies|buckets)\b/i', $sentence, $m)) {
            $name = $m[1];
            $val = $m[2];
            $unit = $m[3];
            $templates = [
                "Official scoring showed $name finishing the contest with $val $unit.",
                "The offensive production was led by $name, who pilled up $val $unit during the clash.",
                "A total of $val $unit was the final count for $name in this showdown.",
                "Contributing significantly, $name notched $val $unit for the squad."
            ];
        }

        // NOISE INJECTION: Add randomized "color" phrases to break sequence detection
        if (!empty($templates)) {
            $finalRegen = $templates[array_rand($templates)];
            
            // 100% chance to inject noise in Nuclear 6.0
            $noise = [
                "Witnesses noted the high intensity of play.",
                "The strategic approach was evident to all observers.",
                "Energy levels remained peak throughout the segment.",
                "This performance aligns with the athlete's recent remarkable trajectory.",
                "The atmosphere definitely favored the standout performer's style."
            ];
            $finalRegen .= " " . $noise[array_rand($noise)];
            
            return $finalRegen;
        }

        return null; 
    }
    
    /**
     * Apply synonym replacements to text
     */
    private function applyWordVariations($text) {
        foreach ($this->synonyms as $original => $replacements) {
            $pattern = '/\b' . preg_quote($original, '/') . '\b/i';
            
            // NUCLEAR 3.0: 100% replacement chance for tracked synonyms
            $replacement = $replacements[array_rand($replacements)];
            
            $text = preg_replace_callback($pattern, function($matches) use ($replacement) {
                if (ctype_upper($matches[0][0])) {
                    return ucfirst($replacement);
                }
                return $replacement;
            }, $text); 
        }
        
        return $text;
    }
    
    private function varyStructure($sentence) {
        // STRATEGY A: Clause Flipping (Flip "X while Y" or "X because Y")
        if (preg_match('/^(.*?),?\s+(while|because|although|as|since)\s+(.*)$/i', $sentence, $m)) {
            $clause1 = trim($m[1], " ,");
            $conjunction = $m[2];
            $clause2 = trim($m[3], " ,");
            
            if (rand(1, 10) > 4) {
                return ucfirst($conjunction) . " " . $clause2 . ", " . strtolower(substr($clause1, 0, 1)) . substr($clause1, 1);
            }
        }
        
        // STRATEGY B: Active to Passive Transformation
        if (preg_match('/^([A-Z][a-z]+)\s+(scored|piled up|tallied|notched|recorded|accounted for)\s+(\d+)\s+(points|tallies|buckets|markers|notches)\b(.*)$/i', $sentence, $m)) {
            $name = $m[1];
            $verb = $m[2];
            $count = $m[3];
            $unit = $m[4];
            $rest = $m[5];
            
            $variations = [
                "A total of $count $unit were delivered by $name$rest",
                "$name's offensive effort resulted in $count $unit$rest",
                "Recording $count $unit was the key output for $name$rest"
            ];
            return $variations[array_rand($variations)];
        }

        // STRATEGY C: "In a notable development..." wrapper
        if (preg_match('/^([A-Z][a-z]+(?:\\s+[a-z]+)?)\\s+([a-z]+)\\b/i', $sentence, $m)) {
            $subject = $m[1];
            $verb = $m[2];
            $rest = trim(str_replace($m[0], "", $sentence));
            
            $patterns = [
                "In a noteworthy performance, $subject $verb $rest",
                "Demonstrating excellence, $subject $verb $rest",
                "With impressive skill, $subject $verb $rest"
            ];
            return $patterns[array_rand($patterns)];
        }

        return $sentence;
    }
    
    /**
     * NUCLEAR 6.0: Paraphrase quotes from direct to indirect speech.
     * FORCED: Any sentence with quotes is now transformed.
     */
    private function paraphraseQuotes($sentence) {
        $verbs = '(?:said|told|mentioned|shared|commented|remarked|stated|noted|expressed|detailed|affirmed|pointed out|explained)';
        
        // Match 1: "Quote," Name Verb.
        if (preg_match('/^["\'](.*?)["\'],?\s+([A-Z][a-z]+(?: [A-Z][a-z]+)?)\s+('.$verbs.')/i', $sentence, $m)) {
            $quote = trim($m[1], " ,.!?");
            $name = $m[2];
            $verb = $m[3];
            $quote = $this->applyWordVariations($quote);
            
            $templates = [
                "$name $verb that $quote.",
                "While speaking to the media, $name indicated that $quote.",
                "According to $name, $quote.",
                "Highlighting the moment, $name $verb that $quote."
            ];
            return $templates[array_rand($templates)];
        }
        
        // Match 2: Name Verb, "Quote."
        if (preg_match('/^([A-Z][a-z]+(?: [A-Z][a-z]+)?)\s+('.$verbs.')\b,?\s+["\'](.*?)["\']/i', $sentence, $m)) {
            $name = $m[1];
            $verb = $m[2];
            $quote = trim($m[3], " ,.!?");
            $quote = $this->applyWordVariations($quote);

            $templates = [
                "$name $verb that $quote.",
                "Expressing thoughts on the situation, $name noted that $quote.",
                "$name shared that $quote.",
                "It was mentioned by $name that $quote."
            ];
            return $templates[array_rand($templates)];
        }

        // NUCLEAR 6.0: Standalone quotes (no attribute detected in sentence)
        if (preg_match('/["\'](.*?)["\']/', $sentence, $m)) {
            $quote = trim($m[1], " ,.!?");
            $quote = $this->applyWordVariations($quote);
            
            $templates = [
                "It was noted during the event that $quote.",
                "Reports from the scene indicate that $quote.",
                "Observers mentioned that $quote.",
                "The narrative was furthered by the sentiment that $quote."
            ];
            return $templates[array_rand($templates)];
        }
        
        return $sentence;
    }

    /**
     * NUCLEAR 5.0: Clause Reassembly/Shuffling
     * Splits sentences at major conjunctions and swaps them.
     */
    private function shuffleClauses($sentence) {
        // Look for ", and ", ", but ", ", while ", ", as "
        $conjunctions = ['and', 'but', 'while', 'as', 'since', 'although'];
        $pattern = '/^(.+?),\s+(' . implode('|', $conjunctions) . ')\s+(.+)$/i';
        
        if (preg_match($pattern, $sentence, $m)) {
            $clause1 = trim($m[1], " ,");
            $conj = $m[2];
            $clause2 = trim($m[3], " ,");
            
            // Only swap if clauses are substantial
            if (str_word_count($clause1) > 3 && str_word_count($clause2) > 3 && rand(1, 10) > 3) {
                // Determine new conjunction or structure
                $newConjMap = [
                    'and' => ['and furthermore', 'while at the same time', 'plus'],
                    'but' => ['however', 'yet', 'though'],
                    'while' => ['whereas', 'although'],
                    'as' => ['because', 'seeing as'],
                    'since' => ['given that', 'because'],
                    'although' => ['even though', 'while']
                ];
                
                $c = $newConjMap[strtolower($conj)] ?? [$conj];
                $pickedConj = $c[array_rand($c)];
                
                return ucfirst($clause2) . " " . $pickedConj . " " . lcfirst($clause1);
            }
        }
        
        return $sentence;
    }

    /**
     * Add contextual phrases
     */
    private function addContextPhrase($sentence) {
        // If sentence already starts with a prefix, don't add another
        if (preg_match('/^(In a note|Demonstrating|With impressive|Showcasing|Following|It is worth|Remarkably|Notably|Impressively|Significantly|As reported|According to|Observers|During|While|After|Before|Since|Adding to|Additionally|Moreover|Furthermore|Interestingly|Crucially|Importantly|Historically|Typically|Consequently|Thus|Therefore)\b/i', $sentence)) {
            return $sentence;
        }

        $contextPhrases = [
            'remarkably,', 'notably,', 'impressively,', 'significantly,', 'as reported,',
            'according to observers,', 'consequently,', 'furthermore,', 'moreover,',
            'interestingly,', 'crucially,', 'importantly,', 'in a major development,',
            'adding to the narrative,', 'with the eyes of the community watching,',
            'amidst high expectations,', 'showcasing great determination,',
            'leaving a lasting impression,', 'building on previous success,',
            'as the season unfolds,', 'to the delight of the hometown crowd,',
            'exhibiting high-level talent,', 'in a display of pure skill,'
        ];
        
        $phrase = $contextPhrases[array_rand($contextPhrases)];
        
        // Randomly decide between prefix or mid-sentence injection
        if (rand(1, 10) > 4) {
             // Insert after first word if no comma found
             $words = explode(' ', $sentence, 2);
             if (count($words) > 1) {
                 return $words[0] . ', ' . $phrase . ' ' . $words[1];
             }
        }
        
        return ucfirst($phrase) . ' ' . lcfirst($sentence);
    }
    
    /**
     * Format rewritten text as HTML, preserving images
     */
    private function formatContentAsHTML($rewrittenText, $originalHTML, $hasVideos = false) {
        // Extract images from original HTML (they should already have local paths and classes)
        $images = [];
        if (preg_match_all('/<img[^>]+class="article-inline-image"[^>]+>/i', $originalHTML, $matches)) {
            $images = $matches[0];
        }
        
        // Split rewritten text into blocks (could be <p>, <ul>, or <h2>)
        $blocks = explode("\n\n", $rewrittenText);
        
        $html = '';
        $imageIndex = 0;
        $videoInserted = false;
        
        foreach ($blocks as $i => $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            $html .= "$block\n";
            
            // LEAD PLACEMENT: Insert video player placeholder after 1st paragraph
            if ($hasVideos && !$videoInserted && $i === 0) {
                $html .= "<div class=\"video-body-placement\">{{VIDEO_PLAYER}}</div>\n";
                $videoInserted = true;
            }
            
            // MIDDLE PLACEMENT: Insert inline image every 2-3 narrative blocks
            // Only count blocks that are paragraphs or headers for image injection
            if ($imageIndex < count($images) && ($i + 1) % 4 === 0) {
                $html .= "$images[$imageIndex]\n";
                $imageIndex++;
            }
        }
        
        // If article is too short and video wasn't inserted, prepend it
        if ($hasVideos && !$videoInserted) {
            $html = "<div class=\"video-body-placement\">{{VIDEO_PLAYER}}</div>\n" . $html;
        }
        
        return $html;
    }
    
    /**
     * STEP 4: Save to Server
     */
    public function saveToServer($articles) {
        $this->log("=== STEP 4: Saving to Server ===");
        
        $existingItems = [];
        if (file_exists($this->dataFile)) {
            $existingData = json_decode(file_get_contents($this->dataFile), true);
            if (!empty($existingData['items']) && is_array($existingData['items'])) {
                $existingItems = $existingData['items'];
            }
        }
        
        // Merge New and Existing
        $mergedItems = array_merge($articles, $existingItems);
        
        // Deduplicate based on 'key'
        $uniqueItems = [];
        $seenKeys = [];
        foreach ($mergedItems as $item) {
            if (isset($item['key']) && !isset($seenKeys[$item['key']])) {
                $uniqueItems[] = $item;
                $seenKeys[$item['key']] = true;
            }
        }
        
        // Sort by Date (Newest First)
        usort($uniqueItems, function($a, $b) {
            $dateA = strtotime($a['date'] ?? 0);
            $dateB = strtotime($b['date'] ?? 0);
            return $dateB - $dateA; // Descending
        });
        
        // Optional: Limit total archive size (e.g., keep last 500 items to prevent infinite growth)
        if (count($uniqueItems) > 500) {
            $uniqueItems = array_slice($uniqueItems, 0, 500);
        }
        
        $data = [
            'last_updated' => date('c'),
            'total_items' => count($uniqueItems),
            'items' => $uniqueItems
        ];
        
        // Deep clean UTF-8 to prevent encoding errors
        $data = $this->deepCleanUtf8($data);
        
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
    
    if ($jsonData === false) {
        $this->log("JSON ENCODE ERROR: " . json_last_error_msg());
        return false;
    }
    
    if (file_put_contents($this->dataFile, $jsonData)) {
            $this->log("Successfully saved " . count($uniqueItems) . " articles (Merged " . count($articles) . " new)");
            return true;
        }
        
        $this->log("ERROR: Failed to save data");
        return false;
    }
    
    /**
     * Deep clean data for UTF-8 compatibility
     */
    private function deepCleanUtf8($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->deepCleanUtf8($value);
            }
        } elseif (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        return $data;
    }
    
    /**
     * STEP 5: Publish to Page
     */
    public function publishToPage($articles) {
        $this->log("=== STEP 5: Publishing to Page ===");
        
        $htmlOutput = $this->generateHTML($articles);
        file_put_contents(__DIR__ . '/public/news.html', $htmlOutput);
        
        $this->log("Published " . count($articles) . " articles");
        return true;
    }
    public function run() {
        // ✅ LOCK MECHANISM: Prevent concurrent runs
        $lockFile = sys_get_temp_dir() . '/fetch_news.lock';
        $lockHandle = fopen($lockFile, 'c');
        if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
            $this->log("PROCESS ALREADY RUNNING: Exiting to prevent overlap.");
            return false;
        }

        // ✅ CACHE CHECK: Respect timeout
        if ($this->shouldUseCache()) {
            $this->log("CACHE VALID: No update needed.");
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            return $this->loadCachedData();
        }

        // ✅ DAILY LIMIT CHECK
        $dailyCount = $this->getDailyQuota();
        if ($dailyCount >= $this->dailyLimit) {
            $this->log("DAILY LIMIT REACHED ($dailyCount/{$this->dailyLimit}): No new articles will be generated today.");
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            return $this->loadCachedData();
        }
        
        // Adjust maxArticles for this run based on remaining quota
        $remaining = $this->dailyLimit - $dailyCount;
        $this->maxArticles = min($this->maxArticles, $remaining);
        $this->log("Daily Quota: $dailyCount/{$this->dailyLimit}. Generating max $this->maxArticles articles this run.");

        $this->log("========================================");
        $this->log("Starting News Fetch & Process Pipeline");
        $this->log("========================================");
        
        try {
            // Step 1: Collect master content
            $articles = $this->collectMasterContent();
            if (empty($articles)) {
                throw new Exception("No articles collected");
            }
            
            // Step 2: Download & scrape
            $enrichedArticles = $this->downloadAndScrapeContent($articles);
            if (empty($enrichedArticles)) {
                throw new Exception("No articles enriched");
            }
            
            // Step 3: Smart rewriting (NO API)
            $rewrittenArticles = $this->rewriteContent($enrichedArticles);
            
            // Step 4: Save to server
            $this->saveToServer($rewrittenArticles);
            
            // Step 5: Publish
            $this->publishToPage($rewrittenArticles);
            
            // Step 6: Update Daily Quota
            $this->incrementDailyQuota(count($rewrittenArticles));
            
            $this->log("========================================");
            $this->log("Pipeline completed successfully!");
            $this->log("========================================");
            
            $result = ['items' => $rewrittenArticles, 'last_updated' => date('c')];
            
        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $result = $this->loadCachedData();
        }

        // Release lock
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        @unlink($lockFile);
        
        return $result;
    }
    
    // ============================================
    // HELPER METHODS
    // ============================================
    
    protected function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: keep-alive',
            ]
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $this->log("HTTP Error $httpCode for URL: $url");
            return false;
        }
        
        return $result;
    }
    
    protected function parseArticleList($html, $sportKey = '', $sportName = '') {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        $result = [];
        
        // Handle Yahoo Sports structure
        if (strpos($sportKey, 'yahoo_') === 0) {
            // Updated Yahoo selector based on current stream structure
            $articles = $xpath->query("//a[contains(@href, '/article/')]");
            
            foreach ($articles as $a) {
                $url = $a->getAttribute('href');
                if ($url && strpos($url, 'http') !== 0) {
                    $url = 'https://sports.yahoo.com' . $url;
                }
                
                // Get title from h3
                $titleNode = $xpath->query(".//h3", $a)->item(0);
                if (!$titleNode) continue;
                $title = trim($titleNode->nodeValue);
                
                // Get summary
                $summary = '';
                // Yahoo often has summary in a div near the h3
                $summaryNode = $xpath->query(".//div[contains(@class, '1xlkxil') or contains(@class, 'caas-summary')]", $a)->item(0);
                if ($summaryNode) {
                    $summary = trim($summaryNode->nodeValue);
                }
                
                // Get thumbnail from img or style
                $thumbnail = '';
                $imgNode = $xpath->query(".//img", $a)->item(0);
                if ($imgNode) {
                    $thumbnail = $imgNode->getAttribute('src') ?: $imgNode->getAttribute('data-src');
                }
                
                $result[] = [
                    'key' => md5($url),
                    'title' => $title,
                    'url' => $url,
                    'thumbnail' => $thumbnail,
                    'summary' => $summary,
                    'date' => date('Y-m-d H:i:s'),
                    'source' => 'Yahoo Sports',
                    'sport_key' => $sportKey,
                    'sport_category' => $sportName
                ];
            }
        } else {
            // Original MaxPreps logic
            $articles = $xpath->query("//ul[contains(@class, 'articles')]/li");
            
            foreach ($articles as $li) {
                $linkNode = $xpath->query(".//h3/a", $li)->item(0);
                if (!$linkNode) continue;
                
                $title = trim($linkNode->nodeValue);
                $url = $linkNode->getAttribute('href');
                
                if ($url && strpos($url, 'http') !== 0) {
                    $url = 'https://www.maxpreps.com' . $url;
                }
                
                $imgNode = $xpath->query(".//img", $li)->item(0);
                $thumbnail = '';
                if ($imgNode) {
                    $thumbnail = $imgNode->getAttribute('data-lazy-image') 
                        ?: $imgNode->getAttribute('src');
                }
                
                $pNode = $xpath->query(".//p", $li)->item(0);
                $summary = $pNode ? trim($pNode->nodeValue) : '';
                
                $dateNode = $xpath->query(".//div/abbr", $li)->item(0);
                $dateStr = $dateNode ? $dateNode->getAttribute('title') : '';
                $date = $dateStr ? date('Y-m-d H:i:s', strtotime($dateStr)) : date('Y-m-d H:i:s');
                
                $result[] = [
                    'key' => md5($url),
                    'title' => $title,
                    'url' => $url,
                    'thumbnail' => $thumbnail,
                    'summary' => $summary,
                    'date' => $date,
                    'source' => 'MaxPreps',
                    'sport_key' => $sportKey,
                    'sport_category' => $sportName
                ];
            }
        }
        
        return $result;
    }
    
    protected function extractArticleContent($article, $html) {
        if (empty($html)) {
            return array_merge($article, [
                'hero_image_url' => $article['thumbnail'] ?? '',
                'content' => '',
                'raw_content' => '',
                'activity_or_sport' => 'High School Sports'
            ]);
        }

        // Extract high-res image
        $heroImage = '';
        if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $matches)) {
            $heroImage = $matches[1];
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Strategy: Try article-specific containers first
        $selectors = [
            "//div[contains(@class, 'caas-body')]",
            "//div[contains(@class, 'article-text')]",
            "//div[contains(@class, 'article_body')]",
            "//div[contains(@class, 'article-body')]",
            "//div[contains(@id, 'ArticlePanel')]",
            "//div[contains(@class, 'article-content')]",
            "//article"
        ];
        
        $bestNode = null;
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $bestNode = $nodes->item(0);
                break;
            }
        }
        
        // Final fallback to a broad container if nothing specific found
        if (!$bestNode) {
            $nodes = $xpath->query("//div[contains(@id, 'content') and not(contains(@id, 'network'))]");
            if ($nodes->length > 0) $bestNode = $nodes->item(0);
        }
        
        $articleBody = '';
        $rawContent = '';
        
        if ($bestNode) {
            // Create a temporary document to save the node
            $tempDom = new DOMDocument();
            $tempDom->appendChild($tempDom->importNode($bestNode, true));
            $tempXPath = new DOMXPath($tempDom);
            
            // 1. Blacklist Yahoo branding and filter out the hero image if duplicated in the body
            $images = $tempXPath->query(".//img");
            $heroImageHash = !empty($heroImage) ? md5(preg_replace('/\?.*/', '', $heroImage)) : ''; // Simple hash comparison without query params
            
            foreach ($images as $img) {
                $alt = strtolower($img->getAttribute('alt'));
                $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
                $srcHash = !empty($src) ? md5(preg_replace('/\?.*/', '', $src)) : '';

                // Blacklist: Remove Yahoo branding logos or generic tracking pixels
                if (strpos($alt, 'yahoo sports') !== false || strpos($alt, 'yahoo logo') !== false || 
                    strpos($src, 'yahoo.com') !== false && (strpos($src, 'logo') !== false || strpos($src, 'brand') !== false) ||
                    $img->getAttribute('width') == '1' || $img->getAttribute('height') == '1') {
                    if ($img->parentNode) $img->parentNode->removeChild($img);
                    continue;
                }

                // Deduplication: Remove the body image if it's the SAME as the hero image
                // Yahoo often places the main image at the very top of caas-body
                if (!empty($heroImageHash) && $srcHash === $heroImageHash) {
                    if ($img->parentNode) $img->parentNode->removeChild($img);
                    continue;
                }

                // SECURITY: Strip inline styles to prevent absolute/fixed position breakage
                $img->removeAttribute('style');
            }

            // 2. Clean up: Surgically remove video holders and metadata to avoid double images/text
            $removables = $tempXPath->query(
                ".//script | .//style | .//iframe | .//svg | " .
                ".//a[@data-video-id] | .//div[@class='cbss-video-player'] | .//*[contains(@class, 'video-card')] | " .
                ".//*[(@class='ad' or contains(@class, ' ad ') or contains(@class, 'ad-container') or contains(@class, 'ad-unit')) and not(contains(@class, 'article'))] | " .
                ".//*[contains(@class, 'related') and not(contains(@class, 'article'))] | " .
                ".//*[contains(@class, 'share') or contains(@class, 'social') or contains(@class, 'play-btn') or contains(@class, 'video-btn')] | " .
                ".//aside | .//nav"
            );
            
            foreach ($removables as $remove) {
                // Don't remove if it's the root node of our temp document
                if ($remove->parentNode && $remove->parentNode->nodeType !== XML_DOCUMENT_NODE) {
                    $remove->parentNode->removeChild($remove);
                }
            }
            
            $articleBody = $tempDom->saveHTML();

            // Extract raw content with preserved structure using regex on HTML
            $rawContent = preg_replace('/<(p|div|h1|h2|h3|section|aside|blockquote|table)[^>]*>/i', "\n\n", $articleBody);
            $rawContent = preg_replace('/<(br|li|tr)[^>]*>/i', "\n", $rawContent);
            $rawContent = strip_tags($rawContent);
            $rawContent = html_entity_decode($rawContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $rawContent = preg_replace("/\n\s+/", "\n", $rawContent);
            $rawContent = preg_replace("/\n\n+/", "\n\n", $rawContent);
            $rawContent = trim($rawContent);
            
            if (empty($rawContent)) {
                $rawContent = trim($tempDom->textContent);
            }

        }
        
        return array_merge($article, [
            'hero_image_url' => $heroImage ?: $article['thumbnail'],
            'content' => $articleBody,
            'raw_content' => $rawContent,
            'activity_or_sport' => 'High School Sports'
        ]);
    }
    
    private function downloadImage($url, $prefix) {
        if (empty($url)) return '';
        
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        } elseif (strpos($url, 'http') !== 0) {
            // Check if it's a Yahoo URL or MaxPreps
            if (strpos($url, '/') === 0) {
                if (strpos($prefix, 'yahoo') !== false) {
                    $url = 'https://sports.yahoo.com' . $url;
                } else {
                    $url = 'https://www.maxpreps.com' . $url;
                }
            }
        }
        
        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
        if ($ext == 'jpeg') $ext = 'jpg';

        $filename = $prefix . '_' . md5($url) . '.' . $ext;
        $localPath = $this->imageDir . $filename;
        $publicPath = 'assets/news_images/' . $filename;
        
        if (!file_exists($localPath)) {
            $imgContent = @file_get_contents($url);
            if ($imgContent && strlen($imgContent) > 1000) {
                file_put_contents($localPath, $imgContent);
                $this->optimizeImage($localPath, $ext);
                $this->log("Downloaded & Optimized: $filename");
                return $publicPath;
            }
        } else {
            return $publicPath;
        }
        return '';
    }



    private function processBodyImages($htmlContent, $articleKey) {
        if (empty($htmlContent)) return $htmlContent;
        
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');
        
        $imageCount = 0;
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src) {
                $localPath = $this->downloadImage($src, $articleKey . '_img' . $imageCount);
                $img->setAttribute('src', $localPath);
                
                // Set uniform class for all body images
                $img->setAttribute('class', 'article-inline-image');
                
                $img->removeAttribute('srcset');
                $img->removeAttribute('data-lazy-image');
                $imageCount++;
            }
        }
        
        $result = '';
        foreach ($dom->childNodes as $node) {
            if ($node->nodeType !== XML_PI_NODE) {
                $result .= $dom->saveHTML($node);
            }
        }
        
        return $result;
    }
    
    private function extractVideos($html, $articleKey = '') {
        $videos = [];
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        $videoContainers = $xpath->query("//a[@data-video-id]");
        
        if ($videoContainers->length > 0) {
            foreach ($videoContainers as $i => $container) {
                $videoId = $container->getAttribute('data-video-id');
                if (!$videoId) continue;
                
                $thumbUrl = '';
                $imgNode = $xpath->query(".//img", $container)->item(0);
                if ($imgNode) {
                    $thumbUrl = $imgNode->getAttribute('src');
                }
                
                $localThumb = '';
                if ($thumbUrl && $articleKey) {
                    $localThumb = $this->downloadImage($thumbUrl, $articleKey . '_vthumb' . $i);
                }
                
                $videos[] = [
                    'id' => $videoId,
                    'hls_url' => "https://image.maxpreps.io/target/$videoId/hls/{$videoId}_phone.m3u8",
                    'type' => 'hls',
                    'poster' => $localThumb
                ];
            }
        } else {
            // Fallback to regex if DOM structure is unexpected
            if (preg_match_all('/data-video-id="([^"]+)"/i', $html, $matches)) {
                foreach ($matches[1] as $videoId) {
                    $videos[] = [
                        'id' => $videoId,
                        'hls_url' => "https://image.maxpreps.io/target/$videoId/hls/{$videoId}_phone.m3u8",
                        'type' => 'hls',
                        'poster' => ''
                    ];
                }
            }
        }
        
        return $videos;
    }
    
    private function generateHTML($articles) {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>High School Sports News</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .article { background: white; margin-bottom: 30px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .article img { max-width: 100%; height: auto; }
        .article-content { padding: 20px; }
        .article h2 { color: #333; margin-bottom: 10px; }
        .article .meta { color: #666; font-size: 0.9em; margin-bottom: 15px; }
        .article p { line-height: 1.6; color: #444; }
    </style>
</head>
<body>
    <h1 style="text-align: center; color: #333; margin-bottom: 40px;">🏈 High School Sports News</h1>';
        
        foreach ($articles as $article) {
            $html .= '<div class="article">';
            if (!empty($article['hero_image'])) {
                $html .= '<img src="' . htmlspecialchars($article['hero_image']) . '" alt="' . htmlspecialchars($article['headline']) . '">';
            }
            $html .= '<div class="article-content">';
            $html .= '<h2>' . htmlspecialchars($article['headline']) . '</h2>';
            $html .= '<div class="meta">📅 ' . date('F j, Y', strtotime($article['date'])) . ' | 📰 ' . $article['source'] . '</div>';
            $html .= '<p><strong>' . htmlspecialchars($article['description']) . '</strong></p>';
            $html .= '<div>' . $article['content'] . '</div>';
            $html .= '</div></div>';
        }
        
        $html .= '</body></html>';
        return $html;
    }
    
    private function shouldUseCache() {
        return file_exists($this->dataFile) 
            && (time() - filemtime($this->dataFile) < $this->cacheTimeout);
    }


    private function loadCachedData() {
        if (file_exists($this->dataFile)) {
            return json_decode(file_get_contents($this->dataFile), true);
        }
        return ['items' => []];
    }
    
    /**
     * Nuclear 4.0 Distort Format: Breaks sequence detection by manipulating punctuation and spacing.
     */
    private function distortFormat($text) {
        // 80% chance to replace comma with a dash or semicolon
        if (rand(1, 100) <= 80) {
            $text = str_replace(', ', ' -- ', $text);
        }
        
        // 60% chance to inject a space in common unit patterns (e.g., 3- point)
        if (rand(1, 100) <= 60) {
            $text = preg_replace('/(\d+)-(\w+)/i', '$1- $2', $text);
        }
        
        // Occasionally remove terminal punctuation for a "raw" feel
        if (rand(1, 100) <= 20) {
            $text = rtrim($text, '.');
        }

        return $text;
    }

    /**
     * Nuclear 4.0 Scramble Numbers: Mixes numeric and rare word formats.
     */
    private function scrambleNumbers($text) {
        $map = [
            '/\b1\b/' => 'lone',
            '/\b2\b/' => 'dual',
            '/\b10\b/' => 'a decad',
            '/\bfirst\b/i' => 'opening',
            '/\bsecond\b/i' => 'alternate',
            '/\bthird\b/i' => 'tertiary',
            '/\bone\b/i' => 'singular'
        ];
        
        foreach ($map as $pattern => $replacement) {
            // Nuclear 6.0: 100% chance
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }

    private function getDailyQuota() {
        if (!file_exists($this->dailyLimitFile)) return 0;
        $data = json_decode(file_get_contents($this->dailyLimitFile), true);
        if (($data['date'] ?? '') !== date('Y-m-d')) return 0;
        return (int)($data['count'] ?? 0);
    }
    
    private function incrementDailyQuota($count) {
        $current = $this->getDailyQuota();
        $newData = [
            'date' => date('Y-m-d'),
            'count' => $current + $count
        ];
        file_put_contents($this->dailyLimitFile, json_encode($newData));
    }

    /**
     * Placeholder image optimizer (to be expanded later)
     */
    private function optimizeImage($path, $ext) {
        $this->log("Optimizing image: $path ($ext)");
        // Add basic optimization if GD/Imagick exists
        return true;
    }
}

/**
 * Global wrapper for ease of use in templates
 */
function fetchMaxPrepsNews() {
    $manager = new NewsContentManager();
    return $manager->run();
}

// ============================================
// EXECUTION
// ============================================

if ((php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) || !empty($_GET['run'])) {
    $manager = new NewsContentManager();
    $result = $manager->run();
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => !empty($result),
            'total_articles' => count($result['items'] ?? []),
            'last_updated' => $result['last_updated'] ?? null
        ], JSON_PRETTY_PRINT);
    } else {
        echo "✅ Process completed!\n";
        echo "Total articles: " . count($result['items'] ?? []) . "\n";
        echo "Check data/news.json for results\n";
    }
}