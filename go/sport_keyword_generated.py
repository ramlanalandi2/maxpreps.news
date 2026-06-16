"""
===============================================================
  SPORT KEYWORD GENERATOR — 1 Million Keywords → SQL OUTPUT
  Output: sport_keywords/sport_keywords_import.sql
  Table : bray5937_keyword.seo_keywords (id, keyword, slug)
  Ready to import: mysql -u root bray5937_keyword < sport_keywords_import.sql
===============================================================
"""

import re
import itertools
import random
import os
from datetime import datetime

TARGET_TOTAL  = 1_000_000

# ── SQL OUTPUT CONFIG ─────────────────────────────────────────
OUTPUT_DIR    = "sport_keywords"
SQL_FILE      = os.path.join(OUTPUT_DIR, "sport_keywords_import.sql")
DB_NAME       = "bray5937_keyword"
TABLE_NAME    = "seo_keywords"
BATCH_SIZE    = 500   # rows per INSERT statement

MIN_WORDS = 3
MIN_CHARS = 15
MAX_CHARS = 110

# ══════════════════════════════════════════════════════════════
#  SPORT SEED BANKS  (20 sub-categories)
# ══════════════════════════════════════════════════════════════

SEEDS_FOOTBALL = [
    "football transfer news", "Premier League standings", "Champions League results",
    "FIFA World Cup 2026", "La Liga top scorers", "Serie A highlights",
    "Bundesliga match schedule", "Ligue 1 standings", "MLS soccer news",
    "best football players world", "football tactics analysis", "football formation guide",
    "how to play football", "football training drills", "football skills tutorial",
    "soccer dribbling tips", "free kick technique", "corner kick strategy",
    "goalkeeper training tips", "football fitness training", "football diet plan",
    "best football boots review", "football jersey collection", "football stadium tour",
    "fantasy football tips", "football betting strategy", "football live score",
    "football highlights today", "football injury news", "football manager tactics",
    "Cristiano Ronaldo goals", "Lionel Messi stats", "Kylian Mbappe news",
    "Erling Haaland goals", "Jude Bellingham highlights", "Vinicius Junior skills",
    "Manchester United news", "Real Madrid transfer", "Barcelona squad",
    "Liverpool match preview", "Manchester City tactics", "Arsenal news",
    "Chelsea FC updates", "Tottenham Hotspur news", "Juventus transfer",
    "AC Milan lineup", "Inter Milan news", "Bayern Munich squad",
    "Borussia Dortmund news", "PSG transfer rumours", "Atletico Madrid form",
    "youth football development", "football academy tips", "women football World Cup",
    "UEFA Nations League", "Copa America highlights", "AFCON tournament",
    "Asian Cup football", "football referee rules", "offside rule explained",
    "VAR technology football", "football statistics analysis", "expected goals explained",
]

SEEDS_BASKETBALL = [
    "NBA standings today", "NBA playoffs schedule", "NBA Finals highlights",
    "NBA draft prospects", "NBA trade rumors", "NBA player stats",
    "basketball shooting tips", "basketball dribbling drills", "how to dunk basketball",
    "basketball training program", "basketball workout routine", "basketball diet tips",
    "NBA best players list", "LeBron James stats", "Stephen Curry highlights",
    "Kevin Durant analysis", "Giannis Antetokounmpo news", "Luka Doncic highlights",
    "Nikola Jokic stats", "Joel Embiid analysis", "Jayson Tatum highlights",
    "Los Angeles Lakers news", "Golden State Warriors", "Boston Celtics schedule",
    "Milwaukee Bucks lineup", "Denver Nuggets news", "Phoenix Suns updates",
    "basketball defense strategy", "pick and roll tutorial", "zone defense explained",
    "basketball referee rules", "basketball court dimensions", "3 point shooting tips",
    "basketball ball handling", "basketball footwork drills", "slam dunk training",
    "WNBA standings today", "EuroLeague basketball", "FIBA World Cup basketball",
    "NBA 2K tips", "fantasy basketball strategy", "basketball betting tips",
    "youth basketball training", "basketball coaching tips", "college basketball rankings",
    "March Madness predictions", "NBA G League news", "basketball shoe review",
    "best basketball shoes", "basketball jersey collection", "NBA history facts",
]

SEEDS_AMERICAN_FOOTBALL = [
    "NFL standings today", "NFL playoffs bracket", "Super Bowl highlights",
    "NFL draft picks", "NFL trade news", "NFL injury report",
    "NFL betting tips", "fantasy football picks", "NFL quarterback rankings",
    "Patrick Mahomes stats", "Josh Allen highlights", "Lamar Jackson news",
    "Travis Kelce stats", "Tyreek Hill highlights", "Justin Jefferson analysis",
    "Kansas City Chiefs", "San Francisco 49ers", "Philadelphia Eagles",
    "Dallas Cowboys news", "Buffalo Bills schedule", "Baltimore Ravens",
    "American football rules", "how to throw football", "wide receiver routes",
    "offensive line technique", "defensive back tips", "linebacker training",
    "NFL combine training", "football strength training", "NFL play analysis",
    "College football rankings", "CFP playoff bracket", "Heisman Trophy",
    "NFL coaching strategy", "two-minute drill football", "red zone offense",
    "football special teams", "NFL cheerleading guide", "tailgating tips",
    "NFL ticket prices", "Super Bowl party ideas", "NFL jersey buying guide",
    "Madden NFL tips", "NFL Sunday Ticket", "fantasy football waiver wire",
    "NFL draft history", "Pro Bowl selections", "NFL Hall of Fame",
]

SEEDS_TENNIS = [
    "Wimbledon results today", "French Open draw", "US Open highlights",
    "Australian Open schedule", "ATP ranking today", "WTA ranking list",
    "tennis serve technique", "tennis backhand tips", "tennis forehand drills",
    "how to play tennis", "tennis footwork training", "tennis fitness program",
    "Carlos Alcaraz highlights", "Novak Djokovic stats", "Jannik Sinner news",
    "Daniil Medvedev analysis", "Iga Swiatek highlights", "Aryna Sabalenka",
    "Coco Gauff news", "Elena Rybakina highlights", "Rafael Nadal career",
    "Roger Federer legacy", "Serena Williams biography", "Andy Murray news",
    "tennis racket comparison", "best tennis shoes review", "tennis ball guide",
    "tennis string tension", "tennis grip technique", "tennis strategy doubles",
    "tennis umpire rules", "hawkeye tennis system", "tennis tiebreak rules",
    "Davis Cup results", "Billie Jean King Cup", "ATP Finals preview",
    "WTA Finals schedule", "tennis betting tips", "fantasy tennis picks",
    "junior tennis training", "tennis coaching certification", "tennis club tips",
    "indoor tennis benefits", "clay court strategy", "grass court tactics",
    "hard court tennis tips", "tennis diet nutrition", "tennis injury prevention",
    "pickleball vs tennis", "padel tennis explained", "table tennis guide",
]

SEEDS_CRICKET = [
    "IPL 2025 schedule", "IPL live score today", "ICC World Cup 2024",
    "Test cricket schedule", "ODI cricket rankings", "T20 cricket tips",
    "cricket batting technique", "cricket bowling tips", "cricket fielding drills",
    "how to play cricket", "cricket fitness training", "cricket diet plan",
    "Virat Kohli stats", "Rohit Sharma highlights", "MS Dhoni legacy",
    "Babar Azam analysis", "Jos Buttler highlights", "Ben Stokes news",
    "Steve Smith technique", "Pat Cummins bowling", "Jasprit Bumrah tips",
    "India vs Australia cricket", "England vs Pakistan cricket", "Ashes series highlights",
    "cricket ball swing tips", "spin bowling technique", "fast bowling drills",
    "cricket umpire decisions", "DRS review system", "cricket pitch report",
    "cricket betting strategy", "fantasy cricket tips", "cricket auction guide",
    "IPL team analysis", "Big Bash League", "Caribbean Premier League",
    "cricket equipment review", "best cricket bat", "cricket helmet guide",
    "cricket gloves comparison", "cricket shoes review", "cricket academy tips",
    "women cricket World Cup", "cricket coaching guide", "grassroots cricket",
    "cricket history facts", "ODI cricket records", "Test cricket records",
    "cricket world ranking", "ICC rankings explained", "county cricket guide",
]

SEEDS_BASEBALL = [
    "MLB standings today", "MLB playoffs schedule", "World Series highlights",
    "MLB draft picks", "MLB trade deadline", "baseball batting tips",
    "baseball pitching technique", "baseball fielding drills", "how to hit baseball",
    "baseball strength training", "baseball fitness program", "baseball diet plan",
    "Shohei Ohtani stats", "Mike Trout highlights", "Aaron Judge home runs",
    "Freddie Freeman analysis", "Mookie Betts news", "Yordan Alvarez stats",
    "New York Yankees news", "Los Angeles Dodgers", "Houston Astros",
    "Atlanta Braves lineup", "Boston Red Sox", "Chicago Cubs news",
    "baseball pitching mechanics", "curveball tutorial", "fastball tips",
    "baseball batting stance", "home run swing tips", "bunting technique",
    "baseball base running", "sliding technique baseball", "outfield tips",
    "fantasy baseball strategy", "baseball betting tips", "MLB statistics",
    "baseball analytics sabermetrics", "WAR statistic explained", "OPS baseball",
    "Little League baseball", "college baseball tips", "MLB rookie guide",
    "baseball equipment guide", "baseball glove break in", "bat selection tips",
    "baseball cleat comparison", "MLB jersey collection", "baseball history",
    "Negro League history", "Baseball Hall of Fame", "spring training tips",
]

SEEDS_GOLF = [
    "golf swing tips", "golf putting technique", "golf chipping drills",
    "how to play golf", "golf fitness training", "golf mental game",
    "PGA Tour schedule", "Masters tournament highlights", "US Open golf",
    "The Open Championship", "Ryder Cup results", "Presidents Cup",
    "Tiger Woods legacy", "Rory McIlroy news", "Jon Rahm highlights",
    "Scottie Scheffler stats", "Xander Schauffele news", "Collin Morikawa",
    "golf course management", "golf club selection", "golf iron tips",
    "driver swing technique", "fairway wood tips", "golf wedge guide",
    "golf putter comparison", "best golf balls review", "golf bag review",
    "golf shoe comparison", "golf glove tips", "golf rangefinder review",
    "golf handicap explained", "golf scoring system", "birdie eagle explained",
    "golf rules explained", "golf etiquette guide", "golf course design",
    "mini golf tips", "disc golf guide", "footgolf rules",
    "golf betting tips", "fantasy golf picks", "golf simulator review",
    "best golf courses world", "golf travel guide", "golf resort review",
    "women golf tips", "junior golf training", "senior golf tips",
    "golf fitness stretches", "golf injury prevention", "golf diet nutrition",
    "golf lessons cost", "golf coaching tips", "golf academy review",
]

SEEDS_MMA_BOXING = [
    "UFC event schedule", "UFC fight predictions", "UFC fighter rankings",
    "MMA training program", "MMA diet plan", "MMA weight cutting",
    "how to box beginners", "boxing training drills", "boxing fitness workout",
    "Conor McGregor news", "Jon Jones highlights", "Israel Adesanya",
    "Alex Pereira UFC", "Leon Edwards news", "Sean O'Malley highlights",
    "Tyson Fury boxing", "Anthony Joshua fight", "Canelo Alvarez",
    "Oleksandr Usyk news", "Deontay Wilder highlights", "Terence Crawford",
    "boxing punching technique", "jab straight cross", "uppercut tips",
    "hook punch tutorial", "boxing footwork drills", "defense boxing tips",
    "head movement boxing", "counter punching tips", "body shot technique",
    "MMA ground game", "wrestling for MMA", "Brazilian jiu jitsu",
    "Muay Thai basics", "kickboxing training tips", "Judo for MMA",
    "MMA conditioning workout", "sparring tips beginners", "MMA gym review",
    "boxing glove comparison", "MMA shorts review", "mouthguard MMA",
    "UFC betting strategy", "MMA fantasy picks", "boxing prediction tips",
    "women MMA fighters", "boxing weight classes", "UFC Hall of Fame",
    "boxing history greats", "MMA documentary list", "combat sports nutrition",
]

SEEDS_FORMULA1 = [
    "Formula 1 race schedule", "F1 standings 2025", "F1 qualifying results",
    "F1 race highlights", "F1 team standings", "F1 driver rankings",
    "Max Verstappen news", "Lewis Hamilton Ferrari", "Charles Leclerc",
    "Lando Norris highlights", "Carlos Sainz news", "George Russell",
    "Red Bull Racing", "Ferrari F1 team", "McLaren F1 news",
    "Mercedes AMG F1", "Aston Martin F1", "Alpine F1 team",
    "F1 car aerodynamics", "DRS system explained", "F1 pit stop tips",
    "F1 tire strategy", "wet race strategy", "safety car rules",
    "F1 engine power", "hybrid system F1", "F1 budget cap",
    "Monaco Grand Prix", "British Grand Prix", "Italian Grand Prix",
    "Singapore Grand Prix", "Abu Dhabi Grand Prix", "US Grand Prix",
    "F1 betting tips", "F1 fantasy picks", "F1 game tips",
    "NASCAR race schedule", "IndyCar standings", "MotoGP race results",
    "Marc Marquez MotoGP", "Pecco Bagnaia news", "Fabio Quartararo",
    "MotoGP tire strategy", "superbike racing tips", "rally car racing",
    "WRC standings 2025", "Dakar Rally preview", "Le Mans 24 hours",
    "karting tips beginners", "track day tips", "motorsport photography",
]

SEEDS_SWIMMING = [
    "swimming technique tips", "freestyle swimming drills", "breaststroke technique",
    "butterfly stroke tips", "backstroke swimming", "swim workout plan",
    "how to swim faster", "open water swimming", "triathlon swim tips",
    "swim training program", "swimming fitness benefits", "aquatic exercises",
    "Olympic swimming records", "World Aquatics Championship", "swimming world records",
    "Caeleb Dressel swimming", "Katie Ledecky records", "Adam Peaty news",
    "Leon Marchand highlights", "swimming pool workout", "swim cap review",
    "best swimming goggles", "swimsuit comparison", "swim fins review",
    "swimming board drills", "pull buoy training", "kickboard exercises",
    "swimming breathing tips", "flip turn technique", "start block tips",
    "swimming nutrition", "swimmer diet plan", "hydration for swimmers",
    "diving technique tips", "springboard diving", "cliff diving guide",
    "water polo basics", "synchronized swimming", "artistic swimming",
    "surf training tips", "surfing for beginners", "surfboard selection",
    "bodyboarding tips", "kitesurfing basics", "windsurfing guide",
    "paddleboarding tips", "kayaking for beginners", "rowing technique",
    "competitive rowing tips", "dragon boat racing", "canoe sprint",
]

SEEDS_ATHLETICS = [
    "marathon training plan", "how to run faster", "running for beginners",
    "5K training program", "10K race strategy", "half marathon tips",
    "ultramarathon preparation", "trail running tips", "sprint training drills",
    "running shoe comparison", "best marathon shoes", "running form tips",
    "running cadence tips", "VO2 max training", "running heart rate zones",
    "treadmill workout tips", "outdoor running benefits", "track running tips",
    "100m sprint technique", "long jump training", "high jump technique",
    "pole vault guide", "shot put technique", "discus throwing tips",
    "hammer throw guide", "javelin technique", "decathlon training",
    "heptathlon guide", "race walking technique", "steeplechase tips",
    "Eliud Kipchoge marathon", "Usain Bolt legacy", "Sydney McLaughlin",
    "Mondo Duplantis pole vault", "Armand Duplantis", "Faith Kipyegon",
    "athletics world records", "Olympic athletics schedule", "Diamond League",
    "World Athletics Championship", "cross country running", "obstacle course race",
    "Spartan Race training", "Tough Mudder tips", "obstacle race tips",
    "running injury prevention", "shin splints treatment", "runner's knee fix",
    "IT band syndrome", "plantar fasciitis running", "running nutrition tips",
    "energy gel guide", "electrolyte drink sports", "carb loading strategy",
]

SEEDS_CYCLING = [
    "Tour de France 2025", "cycling training plan", "road cycling tips",
    "mountain bike trails", "BMX tricks guide", "cycling fitness program",
    "how to cycle faster", "bike fitting guide", "cycling diet nutrition",
    "Tadej Pogacar news", "Jonas Vingegaard cycling", "Primoz Roglic",
    "Remco Evenepoel highlights", "Wout van Aert cycling", "Mathieu van der Poel",
    "best road bike review", "mountain bike comparison", "gravel bike guide",
    "electric bike review", "cycling helmet review", "bike jersey tips",
    "cycling shoes comparison", "clipless pedal guide", "cycling sunglasses",
    "cycling power meter", "Garmin cycling computer", "cycling GPS review",
    "indoor cycling tips", "Zwift training guide", "Peloton vs Zwift",
    "cycling cadence training", "climbing technique cycling", "descending tips",
    "time trial strategy", "sprint cycling tips", "breakaway tactics",
    "Giro d'Italia route", "Vuelta a Espana", "Paris Roubaix tips",
    "velodrome track cycling", "cyclocross tips", "triathlon cycling",
    "cycling injury prevention", "knee pain cycling fix", "back pain cycling",
    "bike maintenance tips", "tire puncture fix", "chain cleaning guide",
    "bike storage ideas", "cycling travel guide", "Gran Fondo tips",
]

SEEDS_RUGBY = [
    "Rugby World Cup 2027", "Six Nations schedule", "Rugby Championship",
    "Premiership Rugby", "Super Rugby standings", "URC rugby standings",
    "rugby tackle technique", "scrummaging tips", "lineout strategy",
    "how to play rugby", "rugby fitness training", "rugby diet plan",
    "All Blacks team news", "Springboks squad", "England rugby",
    "Ireland rugby team", "France rugby squad", "Australia Wallabies",
    "rugby passing drills", "kicking technique rugby", "rucking tips",
    "maul technique rugby", "rugby defense tips", "breakdown skills",
    "rugby referee rules", "TMO review system", "yellow card rules",
    "rugby betting tips", "fantasy rugby picks", "rugby prediction",
    "rugby league vs union", "Australian rugby league", "NRL standings",
    "State of Origin tips", "rugby sevens Olympics", "beach rugby",
    "touch rugby guide", "flag football tips", "rugby nutrition guide",
    "rugby strength training", "rugby agility drills", "rugby speed training",
    "junior rugby coaching", "women rugby tips", "wheelchair rugby",
]

SEEDS_VOLLEYBALL = [
    "volleyball serving tips", "spiking technique volleyball", "blocking tips volleyball",
    "setting technique volleyball", "volleyball digging drills", "how to play volleyball",
    "volleyball training program", "beach volleyball tips", "indoor volleyball rules",
    "volleyball fitness training", "volleyball diet plan", "volleyball shoe review",
    "Olympic volleyball schedule", "FIVB World Championship", "volleyball rankings",
    "volleyball passing drills", "jump serve technique", "float serve tips",
    "volleyball rotation strategy", "libero position tips", "setter strategy",
    "outside hitter guide", "middle blocker tips", "opposite hitter role",
    "beach volleyball serve", "sand volleyball tips", "beach volleyball fitness",
    "volleyball tournament tips", "volleyball betting tips", "fantasy volleyball",
    "volleyball equipment review", "volleyball net height", "volleyball court size",
    "volleyball referee signals", "rule changes volleyball", "scoring system",
    "high school volleyball", "college volleyball tips", "volleyball scholarship",
    "volleyball coaching certification", "youth volleyball drills", "women volleyball",
]

SEEDS_ESPORTS = [
    "esports betting tips", "esports tournament schedule", "how to go pro gaming",
    "League of Legends tips", "Valorant rank guide", "CS2 tips beginners",
    "Dota 2 strategy", "Overwatch 2 tips", "Fortnite competitive tips",
    "esports team rankings", "esports career guide", "streaming tips Twitch",
    "gaming PC for esports", "best gaming monitor", "gaming mouse review",
    "mechanical keyboard esports", "gaming headset comparison", "esports nutrition",
    "gaming chair review", "gaming desk setup", "esports training program",
    "reaction time training", "aim training tips", "game sense tips",
    "esports mental health", "tilt prevention gaming", "focus training esports",
    "VALORANT agent guide", "CS2 crosshair tips", "League of Legends jungle",
    "Rocket League tips", "Rainbow Six Siege tips", "Apex Legends strategy",
    "Call of Duty warzone", "FIFA competitive tips", "EA Sports FC tactics",
    "esports scholarships", "gaming influencer tips", "esports commentary",
    "esports sponsorship guide", "esports management career", "game developer path",
    "esports analytics tools", "coaching esports career", "pro player routine",
]

SEEDS_OLYMPICS = [
    "Paris Olympics 2024 highlights", "Olympics 2028 Los Angeles", "Olympics medal table",
    "Olympic Games history", "Summer Olympics schedule", "Winter Olympics 2026",
    "Olympic athlete training", "how to qualify Olympics", "Olympic trials guide",
    "Olympic rowing events", "Olympic gymnastics tips", "Olympic weightlifting",
    "Olympic wrestling techniques", "Olympic judo guide", "Olympic taekwondo",
    "Olympic archery tips", "Olympic shooting guide", "Olympic fencing",
    "Olympic equestrian guide", "Olympic sailing tips", "Olympic canoe kayak",
    "Olympic triathlon guide", "Olympic pentathlon", "Olympic BMX racing",
    "Olympic skateboarding tips", "Olympic climbing guide", "Olympic surfing",
    "Olympic breaking dance", "Simone Biles gymnastics", "Armand Duplantis Olympics",
    "Olympic doping rules", "anti-doping guide", "WADA regulations",
    "Paralympic Games schedule", "Paralympic athlete stories", "Paralympic sports list",
    "Special Olympics guide", "Olympic sponsorship", "Olympic volunteer guide",
    "Olympic host city history", "Olympic torch relay", "Olympic opening ceremony",
]

SEEDS_SPORTS_BETTING = [
    "sports betting strategy", "how to bet on sports", "sports betting tips today",
    "best sportsbook review", "Bet365 review guide", "DraftKings review",
    "FanDuel betting tips", "William Hill odds", "Betway sports review",
    "football betting tips", "basketball betting picks", "baseball betting strategy",
    "tennis betting tips", "horse racing betting", "golf betting tips",
    "parlay betting strategy", "moneyline bet explained", "spread betting tips",
    "over under betting", "live betting strategy", "in-play betting tips",
    "bankroll management sports", "value betting explained", "betting odds explained",
    "American odds explained", "decimal odds guide", "fractional odds",
    "sports arbitrage betting", "matched betting guide", "no-lose betting tips",
    "sports prediction models", "betting AI tools", "sports analytics betting",
    "daily fantasy sports", "DFS lineup strategy", "DFS value picks",
    "responsible gambling tips", "sports betting taxes", "legal betting states",
    "offshore betting sites", "crypto sports betting", "esports betting guide",
]

SEEDS_SPORTS_NUTRITION = [
    "sports nutrition guide", "athlete diet plan", "pre workout meal ideas",
    "post workout nutrition", "muscle recovery foods", "sports protein intake",
    "carbohydrate loading guide", "hydration sports tips", "electrolyte drinks",
    "protein shake recipes", "whey protein review", "casein protein benefits",
    "creatine for athletes", "BCAAs for sports", "beta-alanine review",
    "caffeine sports performance", "nitric oxide supplements", "weight cutting tips",
    "making weight sports", "cutting weight safely", "bulking diet athlete",
    "lean muscle diet plan", "sports supplements guide", "best pre-workout",
    "best post-workout shake", "energy gel comparison", "sports bar review",
    "athlete meal prep", "cooking for athletes", "vegan athlete diet",
    "gluten-free athlete", "anti-inflammatory diet sport", "gut health athlete",
    "iron deficiency athlete", "bone density sports", "vitamin D sports",
    "omega-3 for athletes", "magnesium sports recovery", "zinc athlete benefits",
    "sleep for athletes", "recovery tools sports", "ice bath benefits",
    "contrast therapy sport", "foam rolling recovery", "massage gun review",
    "sports injury nutrition", "tendon repair diet", "comeback nutrition plan",
]

SEEDS_SPORTS_TRAINING = [
    "strength training athletes", "speed training program", "agility ladder drills",
    "plyometric training guide", "power training tips", "endurance training plan",
    "sports periodization guide", "peaking for competition", "deload week tips",
    "gym program for athletes", "bodyweight training sport", "resistance band drills",
    "sprint interval training", "hill sprint training", "tempo run guide",
    "fartlek training tips", "long slow distance run", "aerobic base building",
    "anaerobic threshold training", "lactate threshold tips", "heart rate training",
    "zone 2 training benefits", "sports psychology tips", "mental toughness sport",
    "visualization techniques sport", "breathing technique sport", "mindfulness athlete",
    "sport-specific warm up", "dynamic stretching routine", "static stretching tips",
    "mobility training sport", "flexibility program", "injury prevention drills",
    "return to sport protocol", "sports rehabilitation tips", "physio exercises sport",
    "sport massage benefits", "cryotherapy benefits", "sauna for athletes",
    "sleep optimization sport", "napping for performance", "travel recovery tips",
    "altitude training benefits", "heat acclimatization tips", "cold weather training",
    "team sport conditioning", "individual sport training", "youth sport development",
]

SEEDS_SPORTS_EQUIPMENT = [
    "best running shoes 2025", "trail running shoe review", "basketball shoe comparison",
    "football cleat review", "soccer boot comparison", "tennis racket review",
    "badminton racket guide", "cricket bat buying guide", "baseball glove review",
    "golf club set review", "cycling helmet safety", "ski helmet comparison",
    "snowboard binding review", "ski boot fitting", "water sport wetsuit",
    "swimming goggle comparison", "diving mask review", "snorkel set guide",
    "sports watch comparison", "fitness tracker review", "GPS running watch",
    "heart rate monitor sport", "power meter cycling", "sports camera review",
    "GoPro for sports", "drone sports filming", "sports photography tips",
    "gym equipment home", "home gym setup", "squat rack review",
    "dumbbells vs barbells", "resistance band review", "pull-up bar guide",
    "treadmill comparison", "rowing machine review", "exercise bike comparison",
    "elliptical machine tips", "battle ropes guide", "medicine ball workout",
    "kettlebell training tips", "TRX suspension trainer", "balance board sport",
    "foam roller comparison", "massage gun review", "compression wear sport",
    "sports bra review", "athletic shorts guide", "performance socks sport",
]

SEEDS_EXTREME_SPORTS = [
    "skydiving for beginners", "base jumping guide", "paragliding tips",
    "rock climbing beginner", "bouldering tips", "free solo climbing",
    "mountain climbing guide", "Everest climbing tips", "alpine climbing",
    "snowboarding tips beginner", "skiing for beginners", "powder skiing",
    "freestyle skiing tips", "ski jumping technique", "biathlon training",
    "skateboarding tricks", "longboarding tips", "parkour beginners",
    "BMX tricks guide", "mountain biking downhill", "freeride mountain bike",
    "white water rafting", "kayaking extreme tips", "surfing big waves",
    "kiteboarding guide", "windsurfing tricks", "wakeboarding tips",
    "bungee jumping guide", "zip line adventure", "via ferrata tips",
    "canyoning guide", "caving spelunking tips", "open water diving",
    "cave diving guide", "technical diving tips", "wingsuit flying",
    "motocross tricks", "supercross tips", "trials motorcycle",
    "off-road racing tips", "rally driving", "drift car tips",
    "powerboat racing", "jet ski racing", "freediving tips",
]

ALL_SEEDS = list(set(
    SEEDS_FOOTBALL + SEEDS_BASKETBALL + SEEDS_AMERICAN_FOOTBALL +
    SEEDS_TENNIS + SEEDS_CRICKET + SEEDS_BASEBALL + SEEDS_GOLF +
    SEEDS_MMA_BOXING + SEEDS_FORMULA1 + SEEDS_SWIMMING + SEEDS_ATHLETICS +
    SEEDS_CYCLING + SEEDS_RUGBY + SEEDS_VOLLEYBALL + SEEDS_ESPORTS +
    SEEDS_OLYMPICS + SEEDS_SPORTS_BETTING + SEEDS_SPORTS_NUTRITION +
    SEEDS_SPORTS_TRAINING + SEEDS_SPORTS_EQUIPMENT + SEEDS_EXTREME_SPORTS
))

# ══════════════════════════════════════════════════════════════
#  MODIFIER BANKS — sport-specific
# ══════════════════════════════════════════════════════════════

INTENT_MODS = [
    "best", "top 10", "top 5", "ultimate guide to", "complete guide to",
    "beginners guide to", "advanced guide to", "how to improve",
    "how to master", "tips and tricks for", "secrets of",
    "best training for", "how to win at", "proven strategies for",
    "expert tips for", "step by step guide to", "everything about",
    "what you need to know", "most effective", "highest rated",
    "top rated review of", "honest review of", "comparison of",
    "best equipment for", "how to train for", "how to get better at",
    "workout plan for", "diet plan for", "injury prevention for",
]

QUESTION_PREFIXES = [
    "how to start", "how to improve", "how to master", "how to train for",
    "how to get better at", "how to win", "how to become pro at",
    "what is the best", "what are the benefits of", "what are the top",
    "why should you play", "why is it important to", "when to train",
    "where to watch", "who is the best", "can you make money in",
    "should you bet on", "is it worth watching", "how much does it cost",
    "how long to train for", "what are the best tips for",
    "how to get started with", "how to choose the right",
    "what are the rules of", "how to qualify for",
    "what are the pros and cons of", "is it safe to do",
    "how to stay motivated in", "how to recover from",
]

LOCATION_MODS = [
    "in the USA", "in the UK", "in India", "in Australia", "in Brazil",
    "in Germany", "in France", "in Spain", "in Italy", "in Japan",
    "in South Korea", "in South Africa", "in Canada", "in Mexico",
    "in Argentina", "in Portugal", "in Netherlands", "in Belgium",
    "in Pakistan", "in Bangladesh", "in Nigeria", "in Kenya",
    "in Indonesia", "in Malaysia", "in Thailand", "in Philippines",
    "worldwide", "globally", "internationally", "online",
    "near me", "at home",
]

AUDIENCE_MODS = [
    "for beginners", "for kids", "for teenagers", "for women", "for men",
    "for seniors", "for professionals", "for amateurs", "for coaches",
    "for athletes", "for fitness enthusiasts", "for weight loss",
    "for muscle gain", "for endurance", "for speed improvement",
    "for flexibility", "for injury recovery", "for mental health",
    "for stress relief", "for fun", "for competitive players",
    "for recreational players", "for college athletes", "for pro athletes",
]

TIME_MODS = [
    "in 2024", "in 2025", "in 2026", "for 2025 season", "right now",
    "updated 2025", "latest 2025", "new in 2025", "trending 2025",
    "this season", "before competition", "in the off-season",
    "during preseason", "in 30 days", "in 90 days",
]

LONG_TAIL_SUFFIXES = [
    "step by step guide", "for complete beginners", "without a coach",
    "on a tight budget", "from scratch at home", "that actually works",
    "and see results fast", "in less than 30 days", "without equipment",
    "the right way", "like a professional", "without injury risk",
    "with no prior experience", "for maximum performance", "the easy way",
    "comprehensive guide 2025", "complete tutorial 2025",
    "proven strategies and tips", "best practices and tips",
    "common mistakes to avoid", "tools and gear you need",
    "everything you need to know", "honest pros and cons",
    "expert recommendations 2025", "case study and examples",
    "checklist and template", "training schedule included",
    "with meal plan included", "science-backed tips",
    "for peak performance", "endorsed by professionals",
    "reddit community tips", "what experts say",
]

COMPARISON_MODS = [
    "vs which is better", "comparison and review 2025",
    "pros and cons explained", "honest comparison guide",
    "side by side comparison", "which should you choose",
    "which one is worth it",
]

# Category classification map
CATEGORY_MAP = {
    "football":        SEEDS_FOOTBALL,
    "basketball":      SEEDS_BASKETBALL,
    "american_football": SEEDS_AMERICAN_FOOTBALL,
    "tennis":          SEEDS_TENNIS,
    "cricket":         SEEDS_CRICKET,
    "baseball":        SEEDS_BASEBALL,
    "golf":            SEEDS_GOLF,
    "mma_boxing":      SEEDS_MMA_BOXING,
    "motorsport":      SEEDS_FORMULA1,
    "swimming":        SEEDS_SWIMMING,
    "athletics":       SEEDS_ATHLETICS,
    "cycling":         SEEDS_CYCLING,
    "rugby":           SEEDS_RUGBY,
    "volleyball":      SEEDS_VOLLEYBALL,
    "esports":         SEEDS_ESPORTS,
    "olympics":        SEEDS_OLYMPICS,
    "sports_betting":  SEEDS_SPORTS_BETTING,
    "sports_nutrition":SEEDS_SPORTS_NUTRITION,
    "sports_training": SEEDS_SPORTS_TRAINING,
    "sports_equipment":SEEDS_SPORTS_EQUIPMENT,
    "extreme_sports":  SEEDS_EXTREME_SPORTS,
}

# ══════════════════════════════════════════════════════════════
#  HELPERS
# ══════════════════════════════════════════════════════════════

def is_quality(kw: str) -> bool:
    words = kw.strip().split()
    return len(words) >= MIN_WORDS and MIN_CHARS <= len(kw) <= MAX_CHARS

def estimate_volume(tier):
    lo, hi = {1: TIER1_VOL, 2: TIER2_VOL, 3: TIER3_VOL}[tier]
    return random.randint(lo, hi)

def estimate_difficulty(tier):
    lo, hi = {1: (60, 100), 2: (30, 70), 3: (5, 40)}[tier]
    return random.randint(lo, hi)

def estimate_cpc(tier):
    lo, hi = {1: (0.30, 6.00), 2: (0.15, 3.00), 3: (0.05, 1.50)}[tier]
    return round(random.uniform(lo, hi), 2)

def classify_intent(kw):
    kw_l = kw.lower()
    if any(w in kw_l for w in ["buy","price","cost","cheap","deal","coupon","discount",
                                 "shop","order","betting","bet on","odds","wager","ticket"]):
        return "transactional"
    if any(w in kw_l for w in ["how to","tutorial","guide","tips","what is","why","learn",
                                 "explained","benefits","step by step","training","drills","technique"]):
        return "informational"
    if any(w in kw_l for w in ["best","top","review","vs","comparison","alternative",
                                 "recommend","worth","pros and cons","ranking"]):
        return "commercial"
    if any(w in kw_l for w in ["schedule","live score","results","news","standings",
                                 "highlights","today","latest","fixture","lineup"]):
        return "navigational"
    return "informational"

def classify_category(kw):
    kw_l = kw.lower()
    for cat, seeds in CATEGORY_MAP.items():
        if any(s.lower() in kw_l for s in seeds):
            return cat
    return "sport_general"

def make_slug(keyword: str) -> str:
    """Convert keyword to URL-friendly slug matching seo_keywords.slug format."""
    slug = keyword.strip().lower()
    slug = re.sub(r"[^a-z0-9\s-]", "", slug)   # remove special chars
    slug = re.sub(r"[\s_]+", "-", slug)          # spaces → hyphens
    slug = re.sub(r"-+", "-", slug).strip("-")   # collapse hyphens
    return slug

def make_row(keyword, tier=1):
    """Return only fields needed for seo_keywords table: keyword + slug."""
    kw = keyword.strip().lower()
    return (kw, make_slug(kw))

# ══════════════════════════════════════════════════════════════
#  GENERATOR
# ══════════════════════════════════════════════════════════════

def generate_keywords(target: int) -> list:
    seen = set()
    results = []

    def add(kw: str):
        kw = kw.strip().lower()
        if kw and kw not in seen and is_quality(kw):
            seen.add(kw)
            results.append(make_row(kw))

    def flush(it):
        for kw in it:
            add(kw)
            if len(results) >= target:
                return True
        return False

    # Pass 1 — bare seeds (already multi-word)
    for s in ALL_SEEDS:
        add(s)
    if len(results) >= target: return results[:target]

    # Pass 2 — intent + seed
    flush(f"{m} {s}" for m, s in itertools.product(INTENT_MODS, ALL_SEEDS))
    if len(results) >= target: return results[:target]

    # Pass 3 — seed + location
    flush(f"{s} {l}" for s, l in itertools.product(ALL_SEEDS, LOCATION_MODS))
    if len(results) >= target: return results[:target]

    # Pass 4 — seed + time
    flush(f"{s} {t}" for s, t in itertools.product(ALL_SEEDS, TIME_MODS))
    if len(results) >= target: return results[:target]

    # Pass 5 — seed + audience
    flush(f"{s} {a}" for s, a in itertools.product(ALL_SEEDS, AUDIENCE_MODS))
    if len(results) >= target: return results[:target]

    # Pass 6 — question + seed
    flush(f"{q} {s}" for q, s in itertools.product(QUESTION_PREFIXES, ALL_SEEDS))
    if len(results) >= target: return results[:target]

    # Pass 7 — seed + suffix
    flush(f"{s} {sf}" for s, sf in itertools.product(ALL_SEEDS, LONG_TAIL_SUFFIXES))
    if len(results) >= target: return results[:target]

    # Pass 8 — intent + seed + location
    flush(f"{m} {s} {l}" for m, s, l in itertools.product(INTENT_MODS[:15], ALL_SEEDS, LOCATION_MODS[:20]))
    if len(results) >= target: return results[:target]

    # Pass 9 — question + seed + time
    flush(f"{q} {s} {t}" for q, s, t in itertools.product(QUESTION_PREFIXES, ALL_SEEDS, TIME_MODS))
    if len(results) >= target: return results[:target]

    # Pass 10 — seed + audience + time
    flush(f"{s} {a} {t}" for s, a, t in itertools.product(ALL_SEEDS, AUDIENCE_MODS, TIME_MODS))
    if len(results) >= target: return results[:target]

    # Pass 11 — question + seed + suffix
    flush(f"{q} {s} {sf}" for q, s, sf in itertools.product(QUESTION_PREFIXES[:12], ALL_SEEDS, LONG_TAIL_SUFFIXES))
    if len(results) >= target: return results[:target]

    # Pass 12 — seed vs seed (comparison)
    flush(f"{s1} {c} {s2}" for s1, c, s2 in itertools.product(ALL_SEEDS[:80], COMPARISON_MODS, ALL_SEEDS[:80]) if s1 != s2)
    if len(results) >= target: return results[:target]

    # Pass 13 — intent + seed + audience
    flush(f"{m} {s} {a}" for m, s, a in itertools.product(INTENT_MODS[:15], ALL_SEEDS, AUDIENCE_MODS))
    if len(results) >= target: return results[:target]

    # Pass 14 — seed + location + time
    flush(f"{s} {l} {t}" for s, l, t in itertools.product(ALL_SEEDS, LOCATION_MODS[:20], TIME_MODS))
    if len(results) >= target: return results[:target]

    # Pass 15 — question + seed + audience + time
    flush(f"{q} {s} {a} {t}" for q, s, a, t in itertools.product(
        QUESTION_PREFIXES[:10], ALL_SEEDS, AUDIENCE_MODS[:12], TIME_MODS[:8]))

    return results[:target]

# ══════════════════════════════════════════════════════════════
#  SQL WRITER
# ══════════════════════════════════════════════════════════════

def escape_sql(s: str) -> str:
    """Minimal MySQL string escaping."""
    return s.replace("\\", "\\\\").replace("'", "\\'").replace("\n", " ").replace("\r", "")

def write_sql(rows: list, filepath: str) -> None:
    """Write all rows as a single SQL file with batched INSERTs."""
    os.makedirs(os.path.dirname(filepath) or ".", exist_ok=True)
    total = len(rows)
    now   = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    with open(filepath, "w", encoding="utf-8", newline="\n") as f:
        # Header
        f.write("-- ============================================================\n")
        f.write(f"-- Sport Keywords SQL Import — {total:,} rows\n")
        f.write(f"-- Generated: {now}\n")
        f.write(f"-- Import  : mysql -u bray5937_keyword -p {DB_NAME} < {os.path.basename(filepath)}\n")
        f.write("-- ============================================================\n\n")

        f.write(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n")
        f.write(f"USE `{DB_NAME}`;\n\n")

        # CREATE TABLE — full 7-column schema
        f.write(f"CREATE TABLE IF NOT EXISTS `{TABLE_NAME}` (\n")
        f.write("  `id`              INT          NOT NULL AUTO_INCREMENT,\n")
        f.write("  `keyword`         VARCHAR(255) NOT NULL,\n")
        f.write("  `slug`            VARCHAR(255) NOT NULL,\n")
        f.write("  `is_indexed`      TINYINT(1)   NOT NULL DEFAULT 0,\n")
        f.write("  `last_crawled_at` DATETIME     NULL     DEFAULT NULL,\n")
        f.write("  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,\n")
        f.write("  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n")
        f.write("  PRIMARY KEY (`id`),\n")
        f.write("  UNIQUE KEY `uq_slug` (`slug`),\n")
        f.write("  KEY `idx_keyword` (`keyword`),\n")
        f.write("  KEY `idx_is_indexed` (`is_indexed`)\n")
        f.write(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n")

        # Bulk INSERT in batches
        f.write("SET FOREIGN_KEY_CHECKS=0;\n")
        f.write("SET UNIQUE_CHECKS=0;\n")
        f.write("SET autocommit=0;\n\n")

        for batch_start in range(0, total, BATCH_SIZE):
            batch = rows[batch_start : batch_start + BATCH_SIZE]
            f.write(f"INSERT IGNORE INTO `{TABLE_NAME}` (`keyword`, `slug`, `is_indexed`, `last_crawled_at`, `created_at`, `updated_at`) VALUES\n")
            values = []
            for (kw, slug) in batch:
                values.append(f"  ('{escape_sql(kw)}', '{escape_sql(slug)}', 0, NULL, '{now}', '{now}')")
            f.write(",\n".join(values))
            f.write(";\n")

            # COMMIT + progress every 10,000 rows
            if (batch_start + BATCH_SIZE) % 10_000 == 0:
                f.write("COMMIT;\n")
                done = min(batch_start + BATCH_SIZE, total)
                pct  = done / total * 100
                print(f"   ✍ Written {done:>10,} / {total:,} rows ({pct:.1f}%)", end="\r")

        f.write("\nCOMMIT;\n")
        f.write("SET FOREIGN_KEY_CHECKS=1;\n")
        f.write("SET UNIQUE_CHECKS=1;\n")
        f.write("SET autocommit=1;\n")

    size_mb = os.path.getsize(filepath) / (1024 * 1024)
    print(f"\n   💾 Saved: {filepath}  ({size_mb:.1f} MB)")


# ══════════════════════════════════════════════════════════════
#  MAIN
# ══════════════════════════════════════════════════════════════

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    start = datetime.now()

    print(f"\n🏆 SPORT Keyword Generator → SQL Output")
    print(f"   Target     : {TARGET_TOTAL:,} keywords")
    print(f"   Database   : {DB_NAME}.{TABLE_NAME}")
    print(f"   Output SQL : {SQL_FILE}")
    print(f"   Unique seeds: {len(ALL_SEEDS):,} | Categories: {len(CATEGORY_MAP):,}\n")

    print("⚡ Generating keywords…")
    all_kws = generate_keywords(TARGET_TOTAL)
    total = len(all_kws)
    print(f"   ✅ {total:,} unique keywords generated")

    # Sample preview
    print("\n📋 SAMPLE (10 random keywords):")
    for kw, slug in random.sample(all_kws, min(10, total)):
        print(f"   keyword : {kw}")
        print(f"   slug    : {slug}\n")

    print(f"\n⚡ Writing SQL file…")
    write_sql(all_kws, SQL_FILE)

    elapsed = (datetime.now() - start).total_seconds()
    size_mb = os.path.getsize(SQL_FILE) / (1024 * 1024)

    print(f"""
===============================================================
  ✅ DONE! — {elapsed:.1f}s
  Keywords : {total:,}
  SQL File : {SQL_FILE} ({size_mb:.1f} MB)
===============================================================

  HOW TO IMPORT:
  ─────────────────────────────────────────────────────────
  Option A — phpMyAdmin (Live Server):
    1. Open phpMyAdmin on your hosting
    2. Select/create database: {DB_NAME}
    3. Click Import → choose sport_keywords_import.sql
    4. Click Go (may take a few minutes)

  Option B — SSH / Command Line:
    mysql -u bray5937_keyword -p {DB_NAME} < sport_keywords_import.sql

  Option C — XAMPP (Local):
    c:\\xampp\\mysql\\bin\\mysql.exe -u root {DB_NAME} < sport_keywords\\sport_keywords_import.sql
===============================================================
""")

if __name__ == "__main__":
    main()
