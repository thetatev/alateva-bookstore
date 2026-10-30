-- book_details.sql
-- 1) Եթե books աղյուսակում չկան publisher/pages/year սյունակները՝ ավելացնում է դրանք։
-- 2) Լրացնում է հրատարակչությունը, էջերը և տարեթիվը `more_books.sql`-ով ավելացված գրքերի համար (միայն դատարկ դաշտերը՝ ձեր արդեն լրացրածը չի փոխվում)։
-- 3) Բոլոր գրքերի հին նկարները հանում է. բոլորը անցնում են cover.php-ով գեներացված կազմի։
-- ՈՒՇԱԴՐՈՒԹՅՈՒՆ. տվյալները բնօրինակ/առաջին հրատարակության մասին են (տարեթիվ, հրատարակիչ, էջերի թիվը՝ միայն ապահով դեպքերում)։ Ձեր վաճառվող հայերեն հրատարակության տվյալները կարող են տարբերվել. ուղղեք admin_books.php-ից։
SET NAMES utf8mb4;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE books ADD COLUMN publisher VARCHAR(150) DEFAULT NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'publisher');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE books ADD COLUMN pages INT DEFAULT NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'pages');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE books ADD COLUMN year INT DEFAULT NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'year');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE books SET year = IF(year IS NULL OR year = 0, 1858, year) WHERE title_hy = 'Վերք Հայաստանի' AND author = 'Խաչատուր Աբովյան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1880, year) WHERE title_hy = 'Խենթը' AND author = 'Րաֆֆի';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1878, year) WHERE title_hy = 'Ջալալեդդին' AND author = 'Րաֆֆի';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1883, year) WHERE title_hy = 'Կայծեր' AND author = 'Րաֆֆի';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1885, year) WHERE title_hy = 'Սամվել' AND author = 'Րաֆֆի';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1943, year) WHERE title_hy = 'Վարդանանք' AND author = 'Դերենիկ Դեմիրճյան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1890, year) WHERE title_hy = 'Անուշ' AND author = 'Հովհաննես Թումանյան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1894, year) WHERE title_hy = 'Գիքոր' AND author = 'Հովհաննես Թումանյան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1910, year) WHERE title_hy = 'Աբու-Լալա Մահարի' AND author = 'Ավետիք Իսահակյան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1881, year) WHERE title_hy = 'Ռուզան' AND author = 'Մուրացան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1935, year) WHERE title_hy = 'Սիլիհդարի պարտեզները' AND author = 'Զապել Եսայան';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1921, year) WHERE title_hy = 'Երկիր Նաիրի' AND author = 'Եղիշե Չարենց';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1959, year) WHERE title_hy = 'Անլռելի զանգակատուն' AND author = 'Պարույր Սևակ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Harcourt, Brace', publisher), year = IF(year IS NULL OR year = 0, 1943, year) WHERE title_hy = 'Մարդկային կատակերգությունը' AND author = 'Վիլյամ Սարոյան';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Harcourt, Brace', publisher), year = IF(year IS NULL OR year = 0, 1940, year) WHERE title_hy = 'Իմ անունը Արամ' AND author = 'Վիլյամ Սարոյան';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'George Newnes', publisher), year = IF(year IS NULL OR year = 0, 1892, year) WHERE title_hy = 'Շերլոք Հոլմսի արկածները' AND author = 'Արթուր Կոնան Դոյլ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'George Newnes', publisher), year = IF(year IS NULL OR year = 0, 1902, year) WHERE title_hy = 'Բասկերվիլների շունը' AND author = 'Արթուր Կոնան Դոյլ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Collins Crime Club', publisher), year = IF(year IS NULL OR year = 0, 1934, year) WHERE title_hy = 'Սպանություն «Արևելյան էքսպրեսում»' AND author = 'Ագաթա Քրիստի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Collins Crime Club', publisher), year = IF(year IS NULL OR year = 0, 1939, year) WHERE title_hy = 'Եվ ոչ ոք չմնաց' AND author = 'Ագաթա Քրիստի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Collins Crime Club', publisher), year = IF(year IS NULL OR year = 0, 1937, year) WHERE title_hy = 'Սպանություն Նեղոսում' AND author = 'Ագաթա Քրիստի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Doubleday', publisher), pages = IF(pages IS NULL OR pages = 0, 454, pages), year = IF(year IS NULL OR year = 0, 2003, year) WHERE title_hy = 'Դա Վինչիի ծածկագիրը' AND author = 'Դեն Բրաուն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'George Allen & Unwin', publisher), pages = IF(pages IS NULL OR pages = 0, 310, pages), year = IF(year IS NULL OR year = 0, 1937, year) WHERE title_hy = 'Հոբիթը' AND author = 'Ջ. Ռ. Ռ. Տոլկին';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'George Allen & Unwin', publisher), year = IF(year IS NULL OR year = 0, 1954, year) WHERE title_hy = 'Մատանիների տիրակալը' AND author = 'Ջ. Ռ. Ռ. Տոլկին';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Bloomsbury', publisher), pages = IF(pages IS NULL OR pages = 0, 223, pages), year = IF(year IS NULL OR year = 0, 1997, year) WHERE title_hy = 'Հարրի Փոթերը և փիլիսոփայական քարը' AND author = 'Ջ. Կ. Ռոուլինգ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Geoffrey Bles', publisher), year = IF(year IS NULL OR year = 0, 1950, year) WHERE title_hy = 'Առյուծը, կախարդը և զգեստապահարանը' AND author = 'Քլայվ Ստեյփլզ Լյուիս';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Parnassus Press', publisher), year = IF(year IS NULL OR year = 0, 1968, year) WHERE title_hy = 'Երկրածովի կախարդը' AND author = 'Ուրսուլա Լե Գուին';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Chilton Books', publisher), pages = IF(pages IS NULL OR pages = 0, 412, pages), year = IF(year IS NULL OR year = 0, 1965, year) WHERE title_hy = 'Դյուն' AND author = 'Ֆրենք Հերբերտ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Gnome Press', publisher), pages = IF(pages IS NULL OR pages = 0, 255, pages), year = IF(year IS NULL OR year = 0, 1951, year) WHERE title_hy = 'Հիմնադրամ' AND author = 'Այզեկ Ազիմով';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'T. Egerton', publisher), year = IF(year IS NULL OR year = 0, 1813, year) WHERE title_hy = 'Հպարտություն և նախապաշարմունք' AND author = 'Ջեյն Օսթին';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Smith, Elder & Co.', publisher), year = IF(year IS NULL OR year = 0, 1847, year) WHERE title_hy = 'Ջեյն Այր' AND author = 'Շարլոտ Բրոնտե';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Thomas Cautley Newby', publisher), year = IF(year IS NULL OR year = 0, 1847, year) WHERE title_hy = 'Մրրկահույզ լեռնանցք' AND author = 'Էմիլի Բրոնտե';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Macmillan', publisher), pages = IF(pages IS NULL OR pages = 0, 1037, pages), year = IF(year IS NULL OR year = 0, 1936, year) WHERE title_hy = 'Քամուց քշվածները' AND author = 'Մարգարեթ Միչել';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'John Danter', publisher), year = IF(year IS NULL OR year = 0, 1597, year) WHERE title_hy = 'Ռոմեո և Ջուլիետ' AND author = 'Ուիլյամ Շեքսպիր';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Русский вестник (The Russian Messenger)', publisher), year = IF(year IS NULL OR year = 0, 1866, year) WHERE title_hy = 'Ոճիր և պատիժ' AND author = 'Ֆյոդոր Դոստոևսկի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Русский вестник (The Russian Messenger)', publisher), year = IF(year IS NULL OR year = 0, 1880, year) WHERE title_hy = 'Եղբայրներ Կարամազովներ' AND author = 'Ֆյոդոր Դոստոևսկի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Русский вестник (The Russian Messenger)', publisher), year = IF(year IS NULL OR year = 0, 1878, year) WHERE title_hy = 'Աննա Կարենինա' AND author = 'Լև Տոլստոյ';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1869, year) WHERE title_hy = 'Պատերազմ և խաղաղություն' AND author = 'Լև Տոլստոյ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'A. Lacroix, Verboeckhoven & Cie', publisher), year = IF(year IS NULL OR year = 0, 1862, year) WHERE title_hy = 'Թշվառները' AND author = 'Վիկտոր Հյուգո';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Charles Gosselin', publisher), year = IF(year IS NULL OR year = 0, 1831, year) WHERE title_hy = 'Նոտր Դամի տաճարը' AND author = 'Վիկտոր Հյուգո';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Journal des Débats', publisher), year = IF(year IS NULL OR year = 0, 1844, year) WHERE title_hy = 'Մոնտե Կրիստո կոմս' AND author = 'Ալեքսանդր Դյումա';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Le Siècle', publisher), year = IF(year IS NULL OR year = 0, 1844, year) WHERE title_hy = 'Երեք հրացանակիրները' AND author = 'Ալեքսանդր Դյումա';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1840, year) WHERE title_hy = 'Մեր ժամանակի հերոսը' AND author = 'Միխայիլ Լերմոնտով';
UPDATE books SET year = IF(year IS NULL OR year = 0, 1833, year) WHERE title_hy = 'Եվգենի Օնեգին' AND author = 'Ալեքսանդր Պուշկին';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Nicholas Ling and John Trundell', publisher), year = IF(year IS NULL OR year = 0, 1603, year) WHERE title_hy = 'Համլետ' AND author = 'Ուիլյամ Շեքսպիր';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Cassell & Co.', publisher), year = IF(year IS NULL OR year = 0, 1883, year) WHERE title_hy = 'Գանձերի կղզին' AND author = 'Ռոբերտ Լուիս Սթիվենսոն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'W. Taylor', publisher), year = IF(year IS NULL OR year = 0, 1719, year) WHERE title_hy = 'Ռոբինզոն Կրուզո' AND author = 'Դանիել Դեֆո';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Pierre-Jules Hetzel', publisher), year = IF(year IS NULL OR year = 0, 1864, year) WHERE title_hy = 'Ճանապարհորդություն դեպի Երկրի կենտրոն' AND author = 'Ժյուլ Վեռն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Pierre-Jules Hetzel', publisher), year = IF(year IS NULL OR year = 0, 1873, year) WHERE title_hy = 'Ութսուն օրում աշխարհի շուրջ' AND author = 'Ժյուլ Վեռն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'American Publishing Company', publisher), year = IF(year IS NULL OR year = 0, 1876, year) WHERE title_hy = 'Թոմ Սոյերի արկածները' AND author = 'Մարկ Տվեն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Charles Scribner’s Sons', publisher), pages = IF(pages IS NULL OR pages = 0, 127, pages), year = IF(year IS NULL OR year = 0, 1952, year) WHERE title_hy = 'Ծերունին և ծովը' AND author = 'Էռնեսթ Հեմինգուեյ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Editorial Sudamericana', publisher), year = IF(year IS NULL OR year = 0, 1967, year) WHERE title_hy = 'Հարյուր տարվա մենություն' AND author = 'Գաբրիել Գարսիա Մարկես';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Covici Friede', publisher), pages = IF(pages IS NULL OR pages = 0, 107, pages), year = IF(year IS NULL OR year = 0, 1937, year) WHERE title_hy = 'Մկների և մարդկանց մասին' AND author = 'Ջոն Սթայնբեկ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Secker & Warburg', publisher), pages = IF(pages IS NULL OR pages = 0, 328, pages), year = IF(year IS NULL OR year = 0, 1949, year) WHERE title_hy = '1984' AND author = 'Ջորջ Օրուել';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Secker & Warburg', publisher), pages = IF(pages IS NULL OR pages = 0, 112, pages), year = IF(year IS NULL OR year = 0, 1945, year) WHERE title_hy = 'Անասնանոց' AND author = 'Ջորջ Օրուել';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Ballantine Books', publisher), pages = IF(pages IS NULL OR pages = 0, 158, pages), year = IF(year IS NULL OR year = 0, 1953, year) WHERE title_hy = '451 աստիճան ըստ Ֆարենհայթի' AND author = 'Ռեյ Բրեդբերի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Rocco', publisher), year = IF(year IS NULL OR year = 0, 1988, year) WHERE title_hy = 'Ալքիմիկոսը' AND author = 'Պաուլո Կոելյո';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Reynal & Hitchcock', publisher), pages = IF(pages IS NULL OR pages = 0, 96, pages), year = IF(year IS NULL OR year = 0, 1943, year) WHERE title_hy = 'Փոքրիկ իշխանը' AND author = 'Անտուան դը Սենտ-Էքզյուպերի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Macmillan', publisher), year = IF(year IS NULL OR year = 0, 1865, year) WHERE title_hy = 'Ալիսը Հրաշքների աշխարհում' AND author = 'Լյուիս Քերոլ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Rabén & Sjögren', publisher), year = IF(year IS NULL OR year = 0, 1945, year) WHERE title_hy = 'Փիփի Երկարագուլպան' AND author = 'Աստրիդ Լինդգրեն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Macmillan', publisher), year = IF(year IS NULL OR year = 0, 1894, year) WHERE title_hy = 'Ջունգլիների գիրքը' AND author = 'Ռադյարդ Քիփլինգ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Methuen', publisher), year = IF(year IS NULL OR year = 0, 1926, year) WHERE title_hy = 'Վինի Պուխ' AND author = 'Ալան Ալեքսանդր Միլն';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Simon & Schuster', publisher), year = IF(year IS NULL OR year = 0, 1936, year) WHERE title_hy = 'Ինչպես ձեռք բերել բարեկամներ և ազդել մարդկանց վրա' AND author = 'Դեյլ Կարնեգի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Free Press', publisher), pages = IF(pages IS NULL OR pages = 0, 372, pages), year = IF(year IS NULL OR year = 0, 1989, year) WHERE title_hy = 'Բարձր արդյունավետ մարդկանց 7 սովորությունները' AND author = 'Սթիվեն Քովի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Verlag für Jugend und Volk', publisher), year = IF(year IS NULL OR year = 0, 1946, year) WHERE title_hy = 'Մարդը իմաստի որոնման մեջ' AND author = 'Վիկտոր Ֆրանկլ';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Avery', publisher), pages = IF(pages IS NULL OR pages = 0, 320, pages), year = IF(year IS NULL OR year = 0, 2018, year) WHERE title_hy = 'Ատոմային սովորություններ' AND author = 'Ջեյմս Քլիր';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Harvill Secker', publisher), pages = IF(pages IS NULL OR pages = 0, 443, pages), year = IF(year IS NULL OR year = 0, 2014, year) WHERE title_hy = 'Սափիենս. Մարդկության համառոտ պատմություն' AND author = 'Յուվալ Նոյ Հարարի';
UPDATE books SET publisher = IF(publisher IS NULL OR publisher = '', 'Bantam Books', publisher), year = IF(year IS NULL OR year = 0, 1988, year) WHERE title_hy = 'Ժամանակի համառոտ պատմություն' AND author = 'Սթիվեն Հոքինգ';

-- Բոլոր գրքերի կազմը՝ գեներացված
UPDATE books SET cover_image = CONCAT('cover.php?id=', id);
