-- ============================================================
--  SFFF 2026 — Street & Fast Food Fest
--  Database Schema  |  MySQL 8+ / MariaDB 10.6+
--  Festival: 29 Aug - 12 Sep 2026  |  Tunis Lac II
--  Website:  https://www.globalvillagetunisia.com
-- ============================================================
--  CHARACTER SET: utf8mb4  (full Arabic + emoji flags)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS passport_stamps;
DROP TABLE IF EXISTS passport_orders;
DROP TABLE IF EXISTS ticket_type_features;
DROP TABLE IF EXISTS ticket_types;
DROP TABLE IF EXISTS pro_applications;
DROP TABLE IF EXISTS doc_form_submissions;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS document_categories;
DROP TABLE IF EXISTS press_accreditations;
DROP TABLE IF EXISTS sponsor_reservations;
DROP TABLE IF EXISTS exhibitor_reservations;
DROP TABLE IF EXISTS ambassador_reservations;
DROP TABLE IF EXISTS night_performances;
DROP TABLE IF EXISTS festival_nights;
DROP TABLE IF EXISTS country_pavillons;
DROP TABLE IF EXISTS festival_zones;
DROP TABLE IF EXISTS future_food_lab_items;
DROP TABLE IF EXISTS festival_senses;
DROP TABLE IF EXISTS awards;
DROP TABLE IF EXISTS sponsors;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS site_config;
DROP TABLE IF EXISTS festivals;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  0. FESTIVALS
-- ============================================================
CREATE TABLE festivals (
    id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    slug             VARCHAR(60)     NOT NULL UNIQUE,
    name_fr          VARCHAR(200)    NOT NULL,
    name_en          VARCHAR(200)    NOT NULL,
    name_ar          VARCHAR(200)    NOT NULL,
    tagline_fr       TEXT,
    tagline_en       TEXT,
    tagline_ar       TEXT,
    edition          YEAR            NOT NULL,
    venue_name       VARCHAR(200)    NOT NULL DEFAULT 'Tunis Lac II — Lake Pearls',
    venue_address    VARCHAR(300)    NOT NULL DEFAULT 'Corniche Lac II, Tunis, Tunisie',
    venue_lat        DECIMAL(10,7)   DEFAULT 36.8381537,
    venue_lng        DECIMAL(10,7)   DEFAULT 10.2330839,
    venue_gmaps_url  VARCHAR(500),
    start_date       DATE            NOT NULL,
    end_date         DATE            NOT NULL,
    open_time        TIME            NOT NULL DEFAULT '17:00:00',
    close_time       TIME            NOT NULL DEFAULT '01:00:00',
    total_km         DECIMAL(4,2)    NOT NULL DEFAULT 1.80,
    total_pavillons  SMALLINT        NOT NULL DEFAULT 42,
    total_exhibitors SMALLINT        NOT NULL DEFAULT 310,
    entry_fee_dt     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,   -- Gratuite
    is_cashless      TINYINT(1)      NOT NULL DEFAULT 1,       -- RFID bracelets
    og_image_url     VARCHAR(500),
    canonical_url    VARCHAR(500)    NOT NULL DEFAULT 'https://www.globalvillagetunisia.com/',
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO festivals (slug, name_fr, name_en, name_ar, tagline_fr, tagline_en, tagline_ar,
    edition, start_date, end_date, og_image_url) VALUES (
    'sfff-2026',
    'SFFF 2026 — Street & Fast Food Fest',
    'SFFF 2026 — Street & Fast Food Fest',
    'SFFF 2026 — مهرجان الأكل الشعبي والسريع',
    'Un voyage des 5 sens à travers 5 continents, sans visa.',
    'A journey of 5 senses across 5 continents, no visa required.',
    'رحلة الحواس الخمس عبر القارات الخمس، بدون تأشيرة.',
    2026, '2026-08-29', '2026-09-12',
    'https://www.globalvillagetunisia.com/assets/og-preview.jpg');

-- ============================================================
--  1. SITE CONFIG
-- ============================================================
CREATE TABLE site_config (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id INT UNSIGNED      NOT NULL,
    cfg_key     VARCHAR(120)      NOT NULL,
    cfg_value   TEXT              NOT NULL,
    data_type   ENUM('string','integer','boolean','json','date','datetime') NOT NULL DEFAULT 'string',
    description VARCHAR(300),
    updated_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cfg (festival_id, cfg_key),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_config (festival_id, cfg_key, cfg_value, data_type, description) VALUES
(1, 'countdown_target_datetime', '2026-08-29T17:00:00+01:00', 'datetime', 'ISO 8601 datetime for the hero countdown timer'),
(1, 'ticket_sales_open',         '1',                         'boolean',  '1 = sales open; 0 = coming soon'),
(1, 'total_visitors_expected',   '100000',                    'integer',  'Expected visitor count'),
(1, 'rfid_enabled',              '1',                         'boolean',  'Cashless RFID system active'),
(1, 'entry_free',                '1',                         'boolean',  'Site entry is free'),
(1, 'instagram_url',             'https://www.instagram.com/tunisiaglobalvillage/',               'string', ''),
(1, 'facebook_url',              'https://www.facebook.com/profile.php?id=61590895631274',        'string', ''),
(1, 'youtube_url',               'https://www.youtube.com/@SFFF-GlobalVillageTunisia',            'string', ''),
(1, 'contact_email',             'contact@globalvillagetunisia.com',                              'string', ''),
(1, 'maps_url',                  'https://maps.google.com/?q=R6C4+F2M,Tunis',                    'string', 'Venue Google Maps link');

-- ============================================================
--  2. FESTIVAL SENSES  (Le Concept — 5 senses)
-- ============================================================
CREATE TABLE festival_senses (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id INT UNSIGNED      NOT NULL,
    sort_order  TINYINT           NOT NULL DEFAULT 0,
    icon        VARCHAR(60)       NOT NULL,
    title_fr    VARCHAR(100)      NOT NULL,
    title_en    VARCHAR(100),
    title_ar    VARCHAR(100),
    subtitle_fr VARCHAR(150),
    subtitle_en VARCHAR(150),
    subtitle_ar VARCHAR(150),
    desc_fr     TEXT,
    desc_en     TEXT,
    desc_ar     TEXT,
    created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO festival_senses (festival_id, sort_order, icon, title_fr, title_en, title_ar, subtitle_fr, subtitle_en, subtitle_ar, desc_fr, desc_en, desc_ar) VALUES
(1, 1, 'utensils', 'Le Goût',    'Taste', 'الذوق',
 'Odyssée Culinaire',   'Culinary Odyssey',    'رحلة الأطعمة',
 '310+ exposants et 42 pavillons nationaux pour un tour du monde gastronomique.',
 '310+ exhibitors and 42 national pavilions for a world gastronomy tour.',
 '+310 عارضاً و42 جناحاً وطنياً لجولة عالمية غذائية.'),
(1, 2, 'music',    'L''Ouïe',   'Sound', 'السمع',
 '15 Nuits de Rythmes', '15 Nights of Rhythms','15 ليلة من الإيقاعات',
 'Showcases, performances folkloriques et concerts live chaque soirée.',
 'Showcases, folk performances and live concerts every evening.',
 'عروض وموسيقى حية وفلكلور شعبي كل ليلة.'),
(1, 3, 'eye',      'La Vue',    'Sight', 'البصر',
 'Visuels & Lumières',  'Visuals & Lights',    'أضواء وعروض بصرية',
 'Parades des nations et laser mapping spectaculaire sur les eaux du lac.',
 'National parades and spectacular laser mapping on the lake waters.',
 'استعراضات دولية وعروض ليزر على مياه البحيرة.'),
(1, 4, 'wind',     'L''Odorat', 'Smell', 'الشم',
 'World Food Avenue',   'World Food Avenue',   'شارع الأطعمة العالمية',
 'Les effluves des cuisines du monde et des épices rares embaument la corniche.',
 'Scents of world cuisines and rare spices perfume the entire promenade.',
 'عطور مطابخ العالم والبهارات النادرة تملأ الكورنيش.'),
(1, 5, 'hand',     'Le Toucher','Touch', 'اللمس',
 'Fluidité RFID',       'RFID Fluidity',       'تجربة RFID السلسة',
 'Artisanat à découvrir et bracelets cashless RFID pour une expérience fluide.',
 'Crafts to explore and cashless RFID wristbands for a friction-free experience.',
 'حرف يدوية وأساور RFID للدفع اللاتلامسي.');

-- ============================================================
--  3. FESTIVAL ZONES  (8 zones, 1.8 km corniche)
-- ============================================================
CREATE TABLE festival_zones (
    id           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id  INT UNSIGNED      NOT NULL,
    zone_number  TINYINT           NOT NULL,
    km_marker    VARCHAR(20)       NOT NULL,
    title_fr     VARCHAR(150)      NOT NULL,
    title_en     VARCHAR(150),
    title_ar     VARCHAR(150),
    desc_fr      TEXT,
    desc_en      TEXT,
    desc_ar      TEXT,
    continent    ENUM('Afrique','Europe','Asie','Amérique','Océanie','Moyen-Orient','Méditerranée','Global','Tunisie'),
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_zone (festival_id, zone_number),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO festival_zones (festival_id, zone_number, km_marker, title_fr, title_en, title_ar, continent) VALUES
(1, 1, '0.0 – 0.2 km', 'Porte d''Entrée & Accueil',         'Entrance Gate & Welcome',          'بوابة الدخول والاستقبال',  'Global'),
(1, 2, '0.2 – 0.4 km', 'Zone Tunisienne & Méditerranéenne',  'Tunisian & Mediterranean Zone',    'المنطقة التونسية والمتوسطية','Méditerranée'),
(1, 3, '0.4 – 0.6 km', 'Zone Africaine',                    'African Zone',                     'المنطقة الأفريقية',        'Afrique'),
(1, 4, '0.6 – 0.8 km', 'Zone Orientale & Proche-Orient',    'Oriental & Middle-East Zone',      'المنطقة الشرقية والشرق الأوسط','Moyen-Orient'),
(1, 5, '0.8 – 1.0 km', 'Zone Européenne',                   'European Zone',                    'المنطقة الأوروبية',        'Europe'),
(1, 6, '1.0 – 1.3 km', 'World Food Avenue & Food Trucks',   'World Food Avenue & Food Trucks',  'شارع الأطعمة والعربات',    'Global'),
(1, 7, '1.3 – 1.6 km', 'Future Food Lab & Scène Principale','Future Food Lab & Main Stage',     'مختبر الغذاء والمسرح الرئيسي','Global'),
(1, 8, '1.6 – 1.8 km', 'Global Market & Village RFID',      'Global Market & RFID Village',     'السوق العالمي وقرية RFID', 'Global');

-- ============================================================
--  4. COUNTRY PAVILLONS  (42 national pavilions)
-- ============================================================
CREATE TABLE country_pavillons (
    id               SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id      INT UNSIGNED      NOT NULL,
    zone_id          SMALLINT UNSIGNED,
    sort_order       SMALLINT          NOT NULL DEFAULT 0,
    country_name_fr  VARCHAR(100)      NOT NULL,
    country_name_en  VARCHAR(100),
    country_name_ar  VARCHAR(100),
    flag_emoji       VARCHAR(10)       NOT NULL,
    tag_fr           VARCHAR(80),
    tag_en           VARCHAR(80),
    tag_ar           VARCHAR(80),
    image_path       VARCHAR(300),
    desc_fr          TEXT,
    desc_en          TEXT,
    desc_ar          TEXT,
    soiree_nationale DATE,
    is_featured      TINYINT(1)        NOT NULL DEFAULT 0,
    created_at       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id)    ON DELETE CASCADE,
    FOREIGN KEY (zone_id)     REFERENCES festival_zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO country_pavillons (festival_id, zone_id, sort_order, country_name_fr, country_name_en, country_name_ar, flag_emoji, tag_fr, tag_en, tag_ar, image_path, desc_fr, is_featured) VALUES
(1, 4, 1,  'Palestine', 'Palestine', 'فلسطين',  '🇵🇸', 'Proche-Orient',   'Middle East',  'الشرق الأوسط',  'assets/countrys/ps.webp',
 'Gastronomie ancestrale, Knafeh Napoulsiya, Falafels dorés & Huile d''olive d''exception.', 1),
(1, 2, 2,  'Maroc',     'Morocco',   'المغرب',  '🇲🇦', 'Afrique du Nord', 'North Africa', 'شمال أفريقيا', 'assets/countrys/mar.webp',
 'Couscous Royal, Tajines parfumés aux épices, Pastilla & Thé à la menthe.', 1),
(1, 5, 3,  'Italie',    'Italy',     'إيطاليا', '🇮🇹', 'Europe',          'Europe',       'أوروبا',       'assets/countrys/it.webp',
 'Authentique Pizza Napolitaine au feu de bois, Pâtes fraîches, Gelato & Espresso.', 1),
(1, 5, 4,  'France',    'France',    'فرنسا',   '🇫🇷', 'Europe',          'Europe',       'أوروبا',       'assets/countrys/fr.webp',
 'Haute street food, Crêpes bretonnes, Pâtisseries fines & Fromages affinés.', 1),
(1, 5, 5,  'Espagne',   'Spain',     'إسبانيا', '🇪🇸', 'Europe',          'Europe',       'أوروبا',       'assets/countrys/sp.webp',
 'Paella géante au safran, Tapas gourmandes, Jamón Ibérico & Churros.', 1),
(1, 2, 6,  'Tunisie',   'Tunisia',   'تونس',    '🇹🇳', 'Afrique du Nord', 'North Africa', 'شمال أفريقيا', NULL,
 'Couscous, Brik, Lablabi, Merguez & Harissa — la gastronomie tunisienne dans toute sa splendeur.', 1);

-- ============================================================
--  5. FESTIVAL NIGHTS  (15 themed nights)
-- ============================================================
CREATE TABLE festival_nights (
    id           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id  INT UNSIGNED      NOT NULL,
    night_number TINYINT           NOT NULL,
    night_date   DATE              NOT NULL,
    theme_fr     VARCHAR(200)      NOT NULL,
    theme_en     VARCHAR(200),
    theme_ar     VARCHAR(200),
    sub_theme_fr VARCHAR(300),
    sub_theme_en VARCHAR(300),
    sub_theme_ar VARCHAR(300),
    open_time    TIME              NOT NULL DEFAULT '17:00:00',
    close_time   TIME              NOT NULL DEFAULT '01:00:00',
    is_sold_out  TINYINT(1)        NOT NULL DEFAULT 0,
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_night_num  (festival_id, night_number),
    UNIQUE KEY uq_night_date (festival_id, night_date),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO festival_nights (festival_id, night_number, night_date, theme_fr, theme_en, theme_ar, sub_theme_fr, sub_theme_en, sub_theme_ar) VALUES
(1,  1, '2026-08-29', 'Nuit d''Ouverture — Cérémonie des Nations',  'Opening Night — Ceremony of Nations',       'ليلة الافتتاح — حفل الأمم',
 'Défilé des 42 drapeaux & feu d''artifice inaugural', 'Parade of 42 flags & inaugural fireworks',    'استعراض 42 علم وألعاب نارية'),
(1,  2, '2026-08-30', 'Nuit Méditerranéenne',                       'Mediterranean Night',                       'ليلة البحر المتوسط',
 'Tunisie, Maroc, Algérie, France, Italie',           'Tunisia, Morocco, Algeria, France, Italy',     'تونس والمغرب والجزائر وفرنسا وإيطاليا'),
(1,  3, '2026-08-31', 'Nuit Africaine',                             'African Night',                             'ليلة أفريقيا',
 'Gastronomie et musique sub-saharienne',              'Sub-Saharan gastronomy & music',               'مطبخ وموسيقى جنوب الصحراء'),
(1,  4, '2026-09-01', 'Nuit Orientale — Mille & Une Saveurs',       'Oriental Night — A Thousand and One Flavours','ليلة الشرق — ألف نكهة ونكهة',
 'Palestine, Liban, Turquie & Inde',                  'Palestine, Lebanon, Turkey & India',           'فلسطين ولبنان وتركيا والهند'),
(1,  5, '2026-09-02', 'Nuit Européenne',                            'European Night',                            'ليلة أوروبا',
 'Europe centrale & occidentale',                     'Central & Western Europe',                     'أوروبا الوسطى والغربية'),
(1,  6, '2026-09-03', 'Nuit des Amériques',                         'Americas Night',                            'ليلة الأمريكتين',
 'USA, Mexique, Brésil & Argentine',                  'USA, Mexico, Brazil & Argentina',              'الولايات المتحدة والمكسيك والبرازيل'),
(1,  7, '2026-09-04', 'Nuit Asiatique',                             'Asian Night',                               'ليلة آسيا',
 'Japon, Chine, Corée & Thaïlande',                  'Japan, China, Korea & Thailand',               'اليابان والصين وكوريا وتايلاند'),
(1,  8, '2026-09-05', 'Nuit Street Food Battle',                    'Street Food Battle Night',                  'ليلة معركة الأكل الشعبي',
 'Compétition internationale chefs de rue',           'International street chef competition',        'مسابقة دولية بين طهاة الشارع'),
(1,  9, '2026-09-06', 'Nuit Future Food Lab',                       'Future Food Lab Night',                     'ليلة مختبر الغذاء',
 'Innovation culinaire & robotique',                  'Culinary innovation & robotics',               'ابتكار الطعام والروبوتيك'),
(1, 10, '2026-09-07', 'Nuit VIP & Diplomatie',                      'VIP & Diplomacy Night',                     'ليلة VIP والدبلوماسية',
 'Ambassadeurs, partenaires & invités d''honneur',    'Ambassadors, partners & honoured guests',      'السفراء والشركاء والضيوف الشرفاء'),
(1, 11, '2026-09-08', 'Nuit Enfants & Familles',                    'Children & Families Night',                 'ليلة الأطفال والعائلات',
 'Animations jeunesse & spectacles familiaux',        'Youth activities & family shows',              'برامج الأطفال والعروض العائلية'),
(1, 12, '2026-09-09', 'Nuit Gastronomique — SFFF Awards',           'Gourmet Night — SFFF Awards',               'ليلة الجائزة الكبرى',
 'Remise des Grands Prix SFFF 2026',                  'SFFF 2026 Grand Prix ceremony',                'حفل توزيع جوائز SFFF 2026'),
(1, 13, '2026-09-10', 'Nuit Médias & Créateurs de Contenu',         'Media & Content Creators Night',            'ليلة الإعلام وصناع المحتوى',
 'Influenceurs, presse & live streaming',              'Influencers, press & live streaming',          'المؤثرون والصحافة والبث المباشر'),
(1, 14, '2026-09-11', 'Nuit de la Nostalgie — Retro Street Food',   'Nostalgia Night — Retro Street Food',       'ليلة الحنين',
 'Food trucks rétro & musiques des années 80/90',     'Retro food trucks & 80s/90s music',            'عربات الطعام الكلاسيكية والموسيقى القديمة'),
(1, 15, '2026-09-12', 'Grande Nuit de Clôture — Au Revoir le Monde','Grand Closing Night — Farewell World',      'ليلة الختام الكبرى',
 'Feu d''artifice de clôture & parade finale',        'Closing fireworks & final parade',             'الألعاب النارية الختامية والاستعراض الأخير');

-- ============================================================
--  6. NIGHT PERFORMANCES
-- ============================================================
CREATE TABLE night_performances (
    id           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    night_id     SMALLINT UNSIGNED NOT NULL,
    sort_order   TINYINT           NOT NULL DEFAULT 0,
    artist_name  VARCHAR(200)      NOT NULL,
    genre        VARCHAR(100),
    country      VARCHAR(100),
    stage        VARCHAR(100)      DEFAULT 'Scène Principale',
    start_time   TIME,
    end_time     TIME,
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (night_id) REFERENCES festival_nights(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  7. FUTURE FOOD LAB ITEMS
-- ============================================================
CREATE TABLE future_food_lab_items (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id INT UNSIGNED      NOT NULL,
    sort_order  TINYINT           NOT NULL DEFAULT 0,
    icon        VARCHAR(60)       NOT NULL,
    title_fr    VARCHAR(200)      NOT NULL,
    title_en    VARCHAR(200),
    title_ar    VARCHAR(200),
    desc_fr     TEXT,
    desc_en     TEXT,
    desc_ar     TEXT,
    created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO future_food_lab_items (festival_id, sort_order, icon, title_fr, title_en, title_ar, desc_fr, desc_en, desc_ar) VALUES
(1, 1, 'cpu',    'Impression 3D Alimentaire',    '3D Food Printing',          'طباعة الطعام ثلاثية الأبعاد',
 'Imprimantes culinaires créant des formes complexes en chocolat, sucre et fromage.',
 '3D culinary printers creating complex shapes in chocolate, sugar and cheese.',
 'طابعات الطعام بالشوكولاتة والسكر والجبن.'),
(1, 2, 'zap',   'Robots Préparateurs',           'Preparation Robots',        'روبوتات تحضير الطعام',
 'Bras robotiques préparant des plats avec une précision millimétrique.',
 'Robotic arms preparing dishes with millimetre precision.',
 'أذرع روبوتية تحضر الأطباق بدقة ملليمترية.'),
(1, 3, 'flask', 'Cuisine Moléculaire Live',       'Live Molecular Cuisine',    'الطبخ الجزيئي المباشر',
 'Démonstrations en direct de gastronomie moléculaire par des chefs internationaux.',
 'Live molecular gastronomy demonstrations by international chefs.',
 'عروض الطبخ الجزيئي مع طهاة دوليين.'),
(1, 4, 'trophy','SFFF Culinary Battles',          'SFFF Culinary Battles',     'مسابقات SFFF الطهوية',
 'Compétitions en direct retransmises dans le monde entier.',
 'Live competitions broadcast worldwide.',
 'مسابقات حية تُبث عالمياً.'),
(1, 5, 'leaf',  'Gastronomie Durable',            'Sustainable Gastronomy',    'الطبخ المستدام',
 'Cuisine zéro déchet, produits locaux et packaging écologique.',
 'Zero-waste cooking, local produce and eco-friendly packaging.',
 'طبخ بدون هدر ومنتجات محلية.'),
(1, 6, 'globe', 'Showcooking Diplomatique',        'Diplomatic Showcooking',    'الطبخ الدبلوماسي',
 'Ambassadeurs présentant leurs recettes nationales phares.',
 'Ambassadors presenting their flagship national recipes.',
 'سفراء يقدمون وصفاتهم الوطنية المميزة.');

-- ============================================================
--  8. AWARDS
-- ============================================================
CREATE TABLE awards (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id INT UNSIGNED      NOT NULL,
    sort_order  TINYINT           NOT NULL DEFAULT 0,
    icon        VARCHAR(60)       NOT NULL,
    title_fr    VARCHAR(200)      NOT NULL,
    title_en    VARCHAR(200),
    title_ar    VARCHAR(200),
    category    ENUM('Passeport','Award','Special') NOT NULL DEFAULT 'Award',
    created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO awards (festival_id, sort_order, icon, title_fr, title_en, title_ar, category) VALUES
(1, 1, 'trophy',    'Grand Prix SFFF 2026 — Meilleur Pavillon',  'SFFF 2026 Grand Prix — Best Pavilion', 'الجائزة الكبرى — أفضل جناح',  'Award'),
(1, 2, 'star',      'Médaille d''Or — Meilleure Street Food',     'Gold Medal — Best Street Food',        'ميدالية ذهبية — أفضل أكل',    'Award'),
(1, 3, 'award',     'Prix de l''Innovation Culinaire',            'Culinary Innovation Prize',            'جائزة الابتكار الغذائي',        'Award'),
(1, 4, 'flag',      'Prix Diplomatie & Culture',                 'Diplomacy & Culture Prize',            'جائزة الدبلوماسية والثقافة',   'Award'),
(1, 5, 'book-open', 'Passeport Collector Complet (42 tampons)',   'Complete Collector Passport (42 stamps)','جواز السفر الكامل (42 ختماً)', 'Passeport'),
(1, 6, 'gift',      'Tirage au sort — Pack VIP offert',           'Lucky Draw — Free VIP Pack',           'سحب قرعة — باقة VIP مجانية',  'Special');

-- ============================================================
--  9. TICKET TYPES
-- ============================================================
CREATE TABLE ticket_types (
    id             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id    INT UNSIGNED      NOT NULL,
    sort_order     TINYINT           NOT NULL DEFAULT 0,
    name_fr        VARCHAR(100)      NOT NULL,
    name_en        VARCHAR(100),
    name_ar        VARCHAR(100),
    tag            VARCHAR(50),
    price_dt       DECIMAL(10,2)     NOT NULL,
    early_bird_dt  DECIMAL(10,2),
    desc_fr        TEXT,
    desc_en        TEXT,
    desc_ar        TEXT,
    cover_style    VARCHAR(50)       NOT NULL DEFAULT 'book-cover-standard',
    is_active      TINYINT(1)        NOT NULL DEFAULT 1,
    max_per_order  TINYINT           NOT NULL DEFAULT 10,
    total_quota    INT,
    sold_count     INT UNSIGNED      NOT NULL DEFAULT 0,
    created_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ticket_types (festival_id, sort_order, name_fr, name_en, name_ar, tag, price_dt, cover_style) VALUES
(1, 1, 'Passeport Standard',     'Standard Passport',   'جواز السفر العادي',      NULL,          25.00, 'book-cover-standard'),
(1, 2, 'Passeport Pro',          'Pro Passport',        'جواز السفر Pro',          'BEST SELLER', 45.00, 'book-cover-pro'),
(1, 3, 'Passeport Diplomatique', 'Diplomatic Passport', 'جواز السفر الدبلوماسي',  NULL,          85.00, 'book-cover-diplomatique'),
(1, 4, 'Passeport VIP',          'VIP Passport',        'جواز السفر VIP',          'VIP',        150.00, 'book-cover-vip');

-- ============================================================
--  10. TICKET TYPE FEATURES
-- ============================================================
CREATE TABLE ticket_type_features (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    ticket_type_id SMALLINT UNSIGNED NOT NULL,
    sort_order     TINYINT           NOT NULL DEFAULT 0,
    feature_fr     VARCHAR(300)      NOT NULL,
    feature_en     VARCHAR(300),
    feature_ar     VARCHAR(300),
    PRIMARY KEY (id),
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ticket_type_features (ticket_type_id, sort_order, feature_fr, feature_en, feature_ar) VALUES
-- Standard (id=1)
(1,1,'Accès Site (1 soir)',                      '1-night site access',                          'دخول الموقع (ليلة واحدة)'),
(1,2,'Bracelet RFID Cashless',                   'RFID Cashless Wristband',                      'سوار RFID اللاتلامسي'),
(1,3,'Accès 42 Pavillons Nationaux',             'Access to 42 National Pavilions',              'دخول 42 جناحاً وطنياً'),
(1,4,'World Food Avenue',                        'World Food Avenue',                            'شارع الأطعمة العالمية'),
(1,5,'Concerts & Shows',                         'Concerts & Shows',                             'حفلات وعروض'),
-- Pro (id=2)
(2,1,'Tout le Standard inclus',                  'Everything in Standard included',              'يشمل كل ما في العادي'),
(2,2,'File Prioritaire (Fast-Track)',             'Priority Lane (Fast-Track)',                   'ممر الأولوية'),
(2,3,'Accès Press Lounge',                       'Press Lounge Access',                          'دخول الصالة الصحفية'),
(2,4,'1 Showcooking Offert',                     '1 Free Showcooking',                           'جلسة طهو مجانية'),
(2,5,'Badge Collector Numéroté',                 'Numbered Collector Badge',                     'شارة جامع مرقمة'),
-- Diplomatique (id=3)
(3,1,'Accès VIP Lounge',                         'VIP Lounge Access',                            'دخول صالة VIP'),
(3,2,'Table Réservée',                           'Reserved Table',                               'طاولة محجوزة'),
(3,3,'Badge Collector Premium',                  'Premium Collector Badge',                      'شارة جامع بريميوم'),
(3,4,'Invitations Soirées Nationales',           'National Night Invitations',                   'دعوات الليالي الوطنية'),
(3,5,'Service Conciergerie',                     'Concierge Service',                            'خدمة الكونسيرج'),
-- VIP (id=4)
(4,1,'Accès Tous Accès — Pass Festival Complet', 'Full Festival All-Access Pass',                'تصريح كامل بالمهرجان'),
(4,2,'Dîner Gastronomique avec Chef Étoilé',     'Gourmet Dinner with Starred Chef',             'عشاء فاخر مع طاهٍ مرموق'),
(4,3,'Balade Privée en Yacht',                   'Private Yacht Ride',                           'جولة خاصة على اليخت'),
(4,4,'Soirée SFFF Awards — Table VIP',           'SFFF Awards Evening — VIP Table',              'حفل الجوائز — طاولة VIP'),
(4,5,'Rencontres Privées Chefs & Ambassadeurs',  'Private Meet & Greet with Chefs & Ambassadors','لقاءات خاصة مع الطهاة والسفراء');

-- ============================================================
--  11. USERS
-- ============================================================
CREATE TABLE users (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    email          VARCHAR(255)      NOT NULL UNIQUE,
    phone          VARCHAR(30),
    full_name      VARCHAR(200),
    lang_pref      ENUM('fr','en','ar') NOT NULL DEFAULT 'fr',
    email_verified TINYINT(1)        NOT NULL DEFAULT 0,
    created_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  12. PASSPORT ORDERS
-- ============================================================
CREATE TABLE passport_orders (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    reference      VARCHAR(30)       NOT NULL UNIQUE,
    festival_id    INT UNSIGNED      NOT NULL,
    ticket_type_id SMALLINT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED,
    buyer_name     VARCHAR(200)      NOT NULL,
    buyer_email    VARCHAR(255)      NOT NULL,
    buyer_phone    VARCHAR(30)       NOT NULL,
    quantity       TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    unit_price_dt  DECIMAL(10,2)     NOT NULL,
    total_price_dt DECIMAL(10,2)     NOT NULL,
    rfid_bracelet  VARCHAR(64),
    status         ENUM('pending','confirmed','paid','cancelled','refunded') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(60),
    paid_at        DATETIME,
    cancelled_at   DATETIME,
    lang           ENUM('fr','en','ar') NOT NULL DEFAULT 'fr',
    ip_address     VARCHAR(45),
    created_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_buyer_email (buyer_email),
    INDEX idx_status      (status),
    INDEX idx_festival    (festival_id),
    FOREIGN KEY (festival_id)    REFERENCES festivals(id)    ON DELETE RESTRICT,
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id)        REFERENCES users(id)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  13. PASSPORT STAMPS
-- ============================================================
CREATE TABLE passport_stamps (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED      NOT NULL,
    pavillon_id SMALLINT UNSIGNED NOT NULL,
    stamped_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stamp (order_id, pavillon_id),
    FOREIGN KEY (order_id)    REFERENCES passport_orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (pavillon_id) REFERENCES country_pavillons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  14. SPONSORS
-- ============================================================
CREATE TABLE sponsors (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id    INT UNSIGNED NOT NULL,
    sort_order     TINYINT      NOT NULL DEFAULT 0,
    name           VARCHAR(200) NOT NULL,
    tier           ENUM('Main Partner','Gold','Silver','Bronze','Media Partner','Institutional') NOT NULL DEFAULT 'Bronze',
    logo_path      VARCHAR(300),
    website_url    VARCHAR(500),
    description_fr TEXT,
    description_en TEXT,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sponsors (festival_id, sort_order, name, tier, logo_path, website_url) VALUES
(1, 1, 'Albuhaira',              'Main Partner',  'assets/logo_albuhaira.png', 'https://www.albuhairainvest.com'),
(1, 2, 'Comcom',                 'Gold',          'assets/comcom.webp',        NULL),
(1, 3, 'Winkom',                 'Gold',          'assets/logo_winkom.webp',   'https://www.facebook.com/winkomofficielle/'),
(1, 4, 'Inspiring Tunisia',      'Media Partner', 'assets/logo_0.png',         'https://www.explore-tunisia.com/fr'),
(1, 5, 'Commune de La Goulette', 'Institutional', 'assets/lagoulette.webp',    'http://www.commune-lagoulette.gov.tn/');

-- ============================================================
--  15. DOCUMENT CATEGORIES
-- ============================================================
CREATE TABLE document_categories (
    id         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    key_name   VARCHAR(60)      NOT NULL UNIQUE,
    icon       VARCHAR(60)      NOT NULL DEFAULT 'file',
    label_fr   VARCHAR(150)     NOT NULL,
    label_en   VARCHAR(150),
    label_ar   VARCHAR(150),
    sort_order TINYINT          NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO document_categories (key_name, icon, label_fr, label_en, label_ar, sort_order) VALUES
('sponsors',   'trophy',       'Sponsors & Partenaires',       'Sponsors & Partners',       'الرعاة والشركاء',            1),
('exposants',  'store',        'Exposants & Kiosques',         'Exhibitors & Kiosks',       'العارضون والكشكات',          2),
('ambassades', 'landmark',     'Ambassades & Diplomatie',      'Embassies & Diplomacy',     'السفارات والدبلوماسية',      3),
('presse',     'tv',           'Média, Presse & Créateurs',    'Media, Press & Creators',   'الإعلام والصحافة والمبدعون', 4),
('b2b',        'shopping-bag', 'Fournisseurs B2B',             'B2B Suppliers',             'الموردون B2B',               5);

-- ============================================================
--  16. DOCUMENTS
-- ============================================================
CREATE TABLE documents (
    id           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id  INT UNSIGNED      NOT NULL,
    category_id  TINYINT UNSIGNED  NOT NULL,
    doc_key      VARCHAR(80)       NOT NULL UNIQUE,
    form_id      VARCHAR(60),
    is_multilang TINYINT(1)        NOT NULL DEFAULT 0,
    file_path    VARCHAR(400),
    file_path_en VARCHAR(400),
    file_path_ar VARCHAR(400),
    title_fr     VARCHAR(300)      NOT NULL,
    title_en     VARCHAR(300),
    title_ar     VARCHAR(300),
    desc_fr      TEXT,
    desc_en      TEXT,
    desc_ar      TEXT,
    is_essential TINYINT(1)        NOT NULL DEFAULT 0,
    sort_order   SMALLINT          NOT NULL DEFAULT 0,
    is_active    TINYINT(1)        NOT NULL DEFAULT 1,
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id)           ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES document_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO documents (festival_id, category_id, doc_key, form_id, is_multilang, file_path, file_path_en, file_path_ar, title_fr, title_en, title_ar, desc_fr, is_essential) VALUES
(1,1,'sponsor-dossier',     NULL,               0,'assets/documents/dossier-sponsoring-programmation.pdf',NULL,NULL,
 'Dossier de Sponsoring & Programmation','Sponsoring & Programming Dossier','ملف الرعاية والبرمجة',
 'Masterplan stratégique, chronologie des 15 soirées et grille tarifaire early bird.',0),
(1,1,'sponsor-guide',       NULL,               1,'assets/documents/guide-sponsor-fr.pdf','assets/documents/guide-sponsor-en.pdf','assets/documents/guide-sponsor-ar.pdf',
 'Guide Sponsor','Sponsor Guide','دليل الراعي',
 'Guide officiel des packs de partenariat, disponible en FR / EN / AR.',0),
(1,1,'sponsor-fiche',       'sponsor',          0,'assets/documents/fiche-reservation-sponsor.pdf',NULL,NULL,
 'Fiche de Réservation Sponsor','Sponsor Reservation Form','استمارة حجز الراعي',
 'Bon de commande à compléter pour réserver votre pack de partenariat.',1),
(1,2,'exposant-modulaire',  'exposant_modulaire',0,'assets/documents/dossier-reservation-exposant.pdf',NULL,NULL,
 'Réservation — Packs Modulaires & Mobiles','Reservation — Modular & Mobile Packs','ملف الحجز — الباقات المعيارية',
 'Tentes, kiosques et brouettes mobiles.',1),
(1,2,'exposant-business',   'exposant_business', 0,'assets/documents/dossier-reservation-exposant.pdf',NULL,NULL,
 'Réservation — Packs Business & Premium','Reservation — Business & Premium Packs','ملف الحجز — الباقات التجارية',
 'Containers et structures premium.',1),
(1,3,'ambassade-guide',     NULL,               1,'assets/documents/guide-ambassade-fr.pdf','assets/documents/guide-ambassade-en.pdf','assets/documents/guide-ambassade-ar.pdf',
 'Guide des Ambassades','Embassy Guide','دليل السفارات',
 'Zones régionales, pavillons nationaux et protocole diplomatique.',0),
(1,3,'ambassade-fiche',     'ambassade',        0,'assets/documents/fiche-reservation-ambassade.pdf',NULL,NULL,
 'Fiche de Réservation Ambassade','Embassy Reservation Form','استمارة حجز السفارة',
 'Réservez votre pavillon national et vos animations protocolaires.',1),
(1,4,'presse-guide',        NULL,               0,'assets/documents/guide-presse-influenceur.pdf',NULL,NULL,
 'Guide Presse & Influenceurs','Press & Influencer Guide','دليل الصحافة والمؤثرين',
 'Accréditations, Press Lounge, studio streaming et hashtags officiels.',0),
(1,4,'presse-fiche',        'presse',           0,'assets/documents/fiche-accreditation-presse.pdf',NULL,NULL,
 'Demande d''Accréditation Presse','Press Accreditation Request','طلب اعتماد صحفي',
 'Formulaire pour journalistes, créateurs de contenu et photographes.',1),
(1,5,'b2b-guide',           NULL,               0,'assets/documents/guide-b2b.pdf',NULL,NULL,
 'Guide Officiel B2B','Official B2B Guide','الدليل الرسمي B2B',
 'Opportunités pour industriels, équipementiers et fournisseurs CHR.',0);

-- ============================================================
--  17. DOC FORM SUBMISSIONS
-- ============================================================
CREATE TABLE doc_form_submissions (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    document_id   SMALLINT UNSIGNED NOT NULL,
    form_id       VARCHAR(60)       NOT NULL,
    email         VARCHAR(255),
    phone         VARCHAR(30),
    form_data     JSON              NOT NULL,
    pdf_generated TINYINT(1)        NOT NULL DEFAULT 0,
    submitted_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address    VARCHAR(45),
    PRIMARY KEY (id),
    INDEX idx_form_id (form_id),
    INDEX idx_email   (email),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  18. PRESS ACCREDITATIONS
-- ============================================================
CREATE TABLE press_accreditations (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id      INT UNSIGNED NOT NULL,
    reference        VARCHAR(30)  NOT NULL UNIQUE,
    nom              VARCHAR(100) NOT NULL,
    prenom           VARCHAR(100) NOT NULL,
    nationalite      VARCHAR(100),
    passport_cin     VARCHAR(60),
    telephone        VARCHAR(30)  NOT NULL,
    email            VARCHAR(255) NOT NULL,
    organisme        VARCHAR(200),
    categorie        ENUM('PRESS / Média Officiel','CREATOR / Influenceur','PHOTO PASS','DRONE / Captation Aérienne') NOT NULL,
    site_web         VARCHAR(300),
    instagram        VARCHAR(150),
    tiktok           VARCHAR(150),
    youtube          VARCHAR(150),
    audience_totale  VARCHAR(80),
    carte_presse     VARCHAR(80),
    equipements      JSON,
    jours_presence   ENUM('Du 16 au 22 Août','Du 23 au 30 Août','15 Jours Continu'),
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_at      DATETIME,
    badge_issued     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status (status),
    INDEX idx_email  (email),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  19. SPONSOR RESERVATIONS
-- ============================================================
CREATE TABLE sponsor_reservations (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id        INT UNSIGNED NOT NULL,
    reference          VARCHAR(30)  NOT NULL UNIQUE,
    societe            VARCHAR(200) NOT NULL,
    marque             VARCHAR(200),
    matricule          VARCHAR(80),
    secteur            VARCHAR(150),
    signataire         VARCHAR(200) NOT NULL,
    qualite            VARCHAR(100),
    telephone          VARCHAR(30)  NOT NULL,
    email              VARCHAR(255) NOT NULL,
    adresse_siege      VARCHAR(400),
    pack               ENUM(
                           'Sponsor Titulaire (Main Partner) — 100 000 DT',
                           'Pack Or (Gold Partner) — 50 000 DT',
                           'Pack Argent (Silver Partner) — 25 000 DT',
                           'Pack Bronze (Bronze Partner) — 10 000 DT'
                       ) NOT NULL,
    options_activation JSON,
    status             ENUM('pending','contacted','signed','cancelled') NOT NULL DEFAULT 'pending',
    signed_at          DATETIME,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  20. EXHIBITOR RESERVATIONS
-- ============================================================
CREATE TABLE exhibitor_reservations (
    id                 INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    festival_id        INT UNSIGNED      NOT NULL,
    zone_id            SMALLINT UNSIGNED,
    reference          VARCHAR(30)       NOT NULL UNIQUE,
    exhibitor_type     ENUM('modulaire','business') NOT NULL,
    entreprise         VARCHAR(200)      NOT NULL,
    responsable_legal  VARCHAR(200),
    fonction           VARCHAR(150),
    telephone          VARCHAR(30)       NOT NULL,
    adresse_pro        VARCHAR(400),
    email              VARCHAR(255)      NOT NULL,
    type_cuisine       VARCHAR(300),
    zone_preference    VARCHAR(100),
    pack               VARCHAR(300)      NOT NULL,
    rooftop_option     TINYINT(1)        NOT NULL DEFAULT 0,
    equipements_lourds TEXT,
    puissance_kw       DECIMAL(8,2),
    tension            ENUM('220V (Monophasé)','380V (Triphasé)'),
    status             ENUM('pending','waitlist','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id)    ON DELETE RESTRICT,
    FOREIGN KEY (zone_id)     REFERENCES festival_zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  21. AMBASSADOR RESERVATIONS
-- ============================================================
CREATE TABLE ambassador_reservations (
    id                     INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    festival_id            INT UNSIGNED      NOT NULL,
    pavillon_id            SMALLINT UNSIGNED,
    reference              VARCHAR(30)       NOT NULL UNIQUE,
    ambassade              VARCHAR(200)      NOT NULL,
    pays                   VARCHAR(100)      NOT NULL,
    service                VARCHAR(200),
    responsable            VARCHAR(200),
    titre                  VARCHAR(150),
    telephone              VARCHAR(30)       NOT NULL,
    adresse_tunis          VARCHAR(400),
    email                  VARCHAR(255)      NOT NULL,
    zone_regionale         ENUM('African Zone','Mediterranean Zone','Orient Zone','European Zone','America Zone','Global Market'),
    animations             JSON,
    date_evenement         DATE,
    format_pavillon        ENUM(
                               'Pavillon Grand Format — Real Container ProMax (48 m²)',
                               'Pavillon Standard — Container Junior Pro (30 m²)',
                               'Pavillon Kiosque Prestige (15 m²)',
                               'Espace Culturel / Exposition Seule (Sur Devis)'
                           ),
    badges_vip             TINYINT,
    macarons_vehicules     TINYINT,
    facilitation_douaniere TINYINT(1) NOT NULL DEFAULT 0,
    status                 ENUM('pending','in-review','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at             DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (festival_id) REFERENCES festivals(id)         ON DELETE RESTRICT,
    FOREIGN KEY (pavillon_id) REFERENCES country_pavillons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  22. PRO APPLICATIONS  (Espace Professionnels)
-- ============================================================
CREATE TABLE pro_applications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    festival_id INT UNSIGNED NOT NULL,
    reference   VARCHAR(30)  NOT NULL UNIQUE,
    profile     ENUM('Investisseur','Exposant','Sponsor','Ambassade','Distributeur','Presse') NOT NULL,
    full_name   VARCHAR(200) NOT NULL,
    company     VARCHAR(200),
    email       VARCHAR(255) NOT NULL,
    phone       VARCHAR(30),
    message     TEXT,
    lang        ENUM('fr','en','ar') NOT NULL DEFAULT 'fr',
    status      ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status (status),
    INDEX idx_email  (email),
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  INDEXES & VIEWS
-- ============================================================
CREATE INDEX idx_pavillon_featured ON country_pavillons (festival_id, is_featured);
CREATE INDEX idx_doc_category      ON documents         (category_id, is_active);
CREATE INDEX idx_nights_date       ON festival_nights   (festival_id, night_date);
CREATE INDEX idx_orders_reference  ON passport_orders   (reference);

CREATE OR REPLACE VIEW v_order_summary AS
SELECT
    po.reference,
    po.buyer_name,
    po.buyer_email,
    po.buyer_phone,
    tt.name_fr       AS passport_type,
    tt.price_dt      AS unit_price,
    po.quantity,
    po.total_price_dt,
    po.status,
    po.paid_at,
    po.rfid_bracelet,
    po.created_at,
    po.lang
FROM passport_orders po
JOIN ticket_types tt ON tt.id = po.ticket_type_id;

CREATE OR REPLACE VIEW v_stamp_progress AS
SELECT
    po.reference,
    po.buyer_name,
    COUNT(ps.id)                       AS stamps_collected,
    42                                 AS stamps_total,
    ROUND(COUNT(ps.id) / 42 * 100, 1) AS progress_pct
FROM passport_orders po
LEFT JOIN passport_stamps ps ON ps.order_id = po.id
GROUP BY po.id, po.reference, po.buyer_name;

CREATE OR REPLACE VIEW v_nights_status AS
SELECT
    fn.id,
    fn.night_number,
    fn.night_date,
    fn.theme_fr,
    fn.theme_en,
    fn.theme_ar,
    fn.open_time,
    fn.close_time,
    CASE
        WHEN fn.night_date = CURDATE() THEN 'tonight'
        WHEN fn.night_date < CURDATE() THEN 'past'
        ELSE 'upcoming'
    END AS night_status
FROM festival_nights fn;
