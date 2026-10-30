-- more_books.sql
-- Ավելացնում է կատեգորիաներ և ~72 գիրք։ Անվտանգ է կրկնակի գործարկելու համար. գոյություն ունեցող կատեգորիաները/գրքերը չի կրկնօրինակում և չի փոխում։
-- Գործարկեք phpMyAdmin-ում (alateva_db → SQL), ապա բացեք կայքը։
-- ԵՐԿԱՐԱՑՈՒՄ. գները, քանակները և ռեյտինգները դեմո արժեքներ են (ռեյտինգը 0 է, քանի դեռ իրական կարծիքներ չկան). խմբագրեք admin_books.php-ից։
SET NAMES utf8mb4;

-- Կատեգորիաներ (համընկնում է ըստ հայերեն կամ անգլերեն անվան)
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Fiction', 'Գեղարվեստական', 'fa-book', 'Արձակ գրականություն՝ հայ և համաշխարհային հեղինակներից' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Գեղարվեստական' OR name = 'Fiction');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Detective', 'Դետեկտիվ', 'fa-user-secret', 'Հետաքննություններ, առեղծվածներ և լարված սյուժեներ' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Դետեկտիվ' OR name = 'Detective');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Fantasy', 'Ֆենթեզի', 'fa-dragon', 'Կախարդական աշխարհներ և ֆանտաստիկա' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Ֆենթեզի' OR name = 'Fantasy');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Romance', 'Սիրավեպ', 'fa-heart', 'Սիրո և հարաբերությունների պատմություններ' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Սիրավեպ' OR name = 'Romance');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'History', 'Պատմություն', 'fa-landmark', 'Պատմագրություն և պատմական երկեր' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Պատմություն' OR name = 'History');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Self-Help', 'Ինքնազարգացում', 'fa-seedling', 'Սովորույթներ, մտածելակերպ և անձնային աճ' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Ինքնազարգացում' OR name = 'Self-Help');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Classics', 'Դասական', 'fa-feather', 'Դասական գրականության անմահ գործեր' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Դասական' OR name = 'Classics');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Children', 'Մանկական', 'fa-child', 'Գրքեր փոքրիկների և պատանիների համար' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Մանկական' OR name = 'Children');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Science', 'Գիտություն', 'fa-flask', 'Գիտահանրամատչելի գրքեր' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Գիտություն' OR name = 'Science');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Poetry', 'Պոեզիա', 'fa-feather-pointed', 'Բանաստեղծություններ և պոեմներ' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Պոեզիա' OR name = 'Poetry');
INSERT INTO categories (name, name_hy, icon, description)
SELECT 'Adventure', 'Արկածային', 'fa-compass', 'Ճանապարհորդություններ և արկածներ' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name_hy = 'Արկածային' OR name = 'Adventure');

-- Գրքեր
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Wounds of Armenia', 'Վերք Հայաստանի', 'Խաչատուր Աբովյան', 'Հայ նոր գրականության առաջին վեպերից՝ XIX դարի հայ կյանքի պատկերով և հայրենասիրական ուժեղ ոգով։', 3200, 14, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Վերք Հայաստանի' AND author = 'Խաչատուր Աբովյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Fool', 'Խենթը', 'Րաֆֆի', 'Րաֆֆու վեպը՝ ազգային ազատագրության և անձնական ճակատագրի խառնուրդով։', 3500, 9, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Խենթը' AND author = 'Րաֆֆի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Jalaleddin', 'Ջալալեդդին', 'Րաֆֆի', 'Պատմական վեպ՝ հայ ժողովրդի դարավոր պայքարի ու սիրո մասին։', 3400, 7, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ջալալեդդին' AND author = 'Րաֆֆի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Sparks', 'Կայծեր', 'Րաֆֆի', 'Վեպ՝ նվիրված ազատության ձգտմանը և ազգային զարթոնքին։', 3600, 5, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Կայծեր' AND author = 'Րաֆֆի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Samvel', 'Սամվել', 'Րաֆֆի', 'Րաֆֆու պատմական վեպը՝ հայկական ազատագրական շարժման ոգով։', 3600, 0, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Սամվել' AND author = 'Րաֆֆի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Vardanank', 'Վարդանանք', 'Դերենիկ Դեմիրճյան', 'Պատմավեպ Վարդանանց պատերազմի մասին՝ հավատի և հայրենիքի համար մղված պայքարի պատկերով։', 3800, 11, (SELECT id FROM categories WHERE name_hy = 'Պատմություն' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Վարդանանք' AND author = 'Դերենիկ Դեմիրճյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Anush', 'Անուշ', 'Հովհաննես Թումանյան', 'Սիրո և ճակատագրի ողբերգական պոեմ՝ հայկական գյուղական կյանքի ֆոնին։', 1800, 25, (SELECT id FROM categories WHERE name_hy = 'Պոեզիա' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Անուշ' AND author = 'Հովհաննես Թումանյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Tumanyan Tales', 'Հեքիաթներ', 'Հովհաննես Թումանյան', 'Թումանյանի սիրելի հեքիաթների ընտրանի՝ փոքրիկների և մեծերի համար։', 2400, 20, (SELECT id FROM categories WHERE name_hy = 'Մանկական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հեքիաթներ' AND author = 'Հովհաննես Թումանյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Gikor', 'Գիքոր', 'Հովհաննես Թումանյան', 'Փոքրիկ աշակերտի ծանր ճակատագրի մասին ցնցող պատմվածք։', 1500, 13, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Գիքոր' AND author = 'Հովհաննես Թումանյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Abu-Lala Mahari', 'Աբու-Լալա Մահարի', 'Ավետիք Իսահակյան', 'Փիլիսոփայական պոեմ՝ ճշմարտության, արդարության և մարդու ազատության մասին։', 2600, 8, (SELECT id FROM categories WHERE name_hy = 'Պոեզիա' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Աբու-Լալա Մահարի' AND author = 'Ավետիք Իսահակյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Ruzan', 'Ռուզան', 'Մուրացան', 'Մուրացանի դրամատիկ երկը՝ սիրո, պատվի ու ճակատագրի շուրջ։', 2500, 6, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ռուզան' AND author = 'Մուրացան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Gardens of Silihdar', 'Սիլիհդարի պարտեզները', 'Զապել Եսայան', 'Հուշագրական վեպ՝ Կոստանդնուպոլսի հայ համայնքի կյանքի մասին։', 3300, 4, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Սիլիհդարի պարտեզները' AND author = 'Զապել Եսայան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Land of Nairi', 'Երկիր Նաիրի', 'Եղիշե Չարենց', 'Չարենցի ստեղծագործությունը՝ հեգնանքով լի հայացք հայկական ճակատագրին։', 3000, 10, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Երկիր Նաիրի' AND author = 'Եղիշե Չարենց');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Unsilent Belfry', 'Անլռելի զանգակատուն', 'Պարույր Սևակ', 'Սևակի հայտնի պոեմը՝ հայ ժողովրդի հիշողության և հոգևոր ուժի մասին։', 2700, 12, (SELECT id FROM categories WHERE name_hy = 'Պոեզիա' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Անլռելի զանգակատուն' AND author = 'Պարույր Սևակ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Mtnadzor', 'Մթնաձոր', 'Ակսել Բակունց', 'Բակունցի պատմվածքների ժողովածու՝ գյուղական կյանքի նուրբ ու պատկերավոր նկարագրություններով։', 2800, 9, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մթնաձոր' AND author = 'Ակսել Բակունց');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Master', 'Տերը', 'Հրանտ Մաթևոսյան', 'Մաթևոսյանի պատմվածքը՝ հայ գյուղի մարդկանց կերպարներով և կենդանի լեզվով։', 2600, 6, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Տերը' AND author = 'Հրանտ Մաթևոսյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'History of Armenia', 'Հայոց պատմություն', 'Մովսես Խորենացի', 'Հայ պատմագրության հիմնաքար երկը՝ հայոց ծագումնաբանության և հնագույն պատմության մասին։', 5200, 8, (SELECT id FROM categories WHERE name_hy = 'Պատմություն' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հայոց պատմություն' AND author = 'Մովսես Խորենացի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'History of the Armenians', 'Հայոց պատմություն', 'Ագաթանգեղոս', 'Հայաստանի քրիստոնեացման ժամանակաշրջանի կարևոր պատմական աղբյուրներից մեկը։', 4800, 5, (SELECT id FROM categories WHERE name_hy = 'Պատմություն' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հայոց պատմություն' AND author = 'Ագաթանգեղոս');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Human Comedy', 'Մարդկային կատակերգությունը', 'Վիլյամ Սարոյան', 'Սարոյանի վեպը՝ փոքր քաղաքի կյանքի ջերմ ու մարդասիրական պատկերով։', 3900, 12, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մարդկային կատակերգությունը' AND author = 'Վիլյամ Սարոյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'My Name Is Aram', 'Իմ անունը Արամ', 'Վիլյամ Սարոյան', 'Պատմվածքներ՝ հայ տղայի մանկության և ընտանեկան ջերմության մասին։', 3400, 15, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Իմ անունը Արամ' AND author = 'Վիլյամ Սարոյան');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Adventures of Sherlock Holmes', 'Շերլոք Հոլմսի արկածները', 'Արթուր Կոնան Դոյլ', 'Հայտնի խորհրդատու-հետախույզի և դոկտոր Ուոթսոնի առաջին գրավիչ գործերը։', 4200, 18, (SELECT id FROM categories WHERE name_hy = 'Դետեկտիվ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Շերլոք Հոլմսի արկածները' AND author = 'Արթուր Կոնան Դոյլ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Hound of the Baskervilles', 'Բասկերվիլների շունը', 'Արթուր Կոնան Դոյլ', 'Շերլոք Հոլմսի ամենահայտնի հետաքննություններից մեկը՝ առեղծվածային ընտանեկան անեծքով։', 3900, 10, (SELECT id FROM categories WHERE name_hy = 'Դետեկտիվ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Բասկերվիլների շունը' AND author = 'Արթուր Կոնան Դոյլ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Murder on the Orient Express', 'Սպանություն «Արևելյան էքսպրեսում»', 'Ագաթա Քրիստի', 'Հերկյուլ Պուարոն հետաքննում է սպանությունը ձյան մեջ կանգնած գնացքում։', 4100, 16, (SELECT id FROM categories WHERE name_hy = 'Դետեկտիվ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Սպանություն «Արևելյան էքսպրեսում»' AND author = 'Ագաթա Քրիստի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'And Then There Were None', 'Եվ ոչ ոք չմնաց', 'Ագաթա Քրիստի', 'Տասը անծանոթ մարդ հավաքվում են կղզում, և նրանց մեկը մյուսի հետևից սկսում են անհետանալ։', 4000, 20, (SELECT id FROM categories WHERE name_hy = 'Դետեկտիվ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Եվ ոչ ոք չմնաց' AND author = 'Ագաթա Քրիստի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Death on the Nile', 'Սպանություն Նեղոսում', 'Ագաթա Քրիստի', 'Պուարոյի հետաքննությունը՝ Նեղոսով զբոսաշրջության ժամանակ տեղի ունեցած սպանության շուրջ։', 4000, 3, (SELECT id FROM categories WHERE name_hy = 'Դետեկտիվ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Սպանություն Նեղոսում' AND author = 'Ագաթա Քրիստի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Da Vinci Code', 'Դա Վինչիի ծածկագիրը', 'Դեն Բրաուն', 'Լարված թրիլեր՝ ծածկագրերով, արվեստի գործերով և հին գաղտնիքներով։', 4800, 13, (SELECT id FROM categories WHERE name_hy = 'Դետեկտիվ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Դա Վինչիի ծածկագիրը' AND author = 'Դեն Բրաուն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Hobbit', 'Հոբիթը', 'Ջ. Ռ. Ռ. Տոլկին', 'Բիլբո Բեգինսի անսպասելի ճանապարհորդությունը՝ թզուկների, վիշապի և գանձի հետ։', 4500, 17, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հոբիթը' AND author = 'Ջ. Ռ. Ռ. Տոլկին');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Lord of the Rings', 'Մատանիների տիրակալը', 'Ջ. Ռ. Ռ. Տոլկին', 'Միջերկրի մեծ վեպը՝ Մատանու ոչնչացման համար մղվող պայքարի մասին։', 8500, 8, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մատանիների տիրակալը' AND author = 'Ջ. Ռ. Ռ. Տոլկին');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Harry Potter and the Philosopher’s Stone', 'Հարրի Փոթերը և փիլիսոփայական քարը', 'Ջ. Կ. Ռոուլինգ', 'Երիտասարդ կախարդի առաջին տարին Հոգվորթսում՝ ընկերությամբ ու առեղծվածներով։', 5200, 22, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հարրի Փոթերը և փիլիսոփայական քարը' AND author = 'Ջ. Կ. Ռոուլինգ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Lion, the Witch and the Wardrobe', 'Առյուծը, կախարդը և զգեստապահարանը', 'Քլայվ Ստեյփլզ Լյուիս', 'Նարնիայի տարեգրության առաջին գիրքը՝ զգեստապահարանի միջով դեպի կախարդական աշխարհ։', 3800, 12, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Առյուծը, կախարդը և զգեստապահարանը' AND author = 'Քլայվ Ստեյփլզ Լյուիս');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'A Wizard of Earthsea', 'Երկրածովի կախարդը', 'Ուրսուլա Լե Գուին', 'Երիտասարդ կախարդի աճի և սեփական ստվերի դեմ պայքարի պատմություն։', 4200, 0, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Երկրածովի կախարդը' AND author = 'Ուրսուլա Լե Գուին');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Dune', 'Դյուն', 'Ֆրենք Հերբերտ', 'Ֆանտաստիկ դասական՝ անապատային մոլորակի, քաղաքականության և նախասահմանության մասին։', 6800, 9, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Դյուն' AND author = 'Ֆրենք Հերբերտ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Foundation', 'Հիմնադրամ', 'Այզեկ Ազիմով', 'Գիտաֆանտաստիկ սագա՝ գալակտիկական կայսրության անկման և նոր քաղաքակրթության մասին։', 5200, 7, (SELECT id FROM categories WHERE name_hy = 'Ֆենթեզի' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հիմնադրամ' AND author = 'Այզեկ Ազիմով');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Pride and Prejudice', 'Հպարտություն և նախապաշարմունք', 'Ջեյն Օսթին', 'Էլիզաբեթ Բենեթի և միստր Դարսիի սիրո պատմությունը՝ սրամիտ ու դասական։', 3900, 14, (SELECT id FROM categories WHERE name_hy = 'Սիրավեպ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հպարտություն և նախապաշարմունք' AND author = 'Ջեյն Օսթին');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Jane Eyre', 'Ջեյն Այր', 'Շարլոտ Բրոնտե', 'Անկախ ու խիզախ աղջկա ուղին՝ դժվարություններից դեպի սեր և ինքնարժանապատվություն։', 4300, 8, (SELECT id FROM categories WHERE name_hy = 'Սիրավեպ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ջեյն Այր' AND author = 'Շարլոտ Բրոնտե');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Wuthering Heights', 'Մրրկահույզ լեռնանցք', 'Էմիլի Բրոնտե', 'Կրքոտ ու մռայլ սիրո պատմություն՝ անտառային անապատում կանգնած տանը։', 4000, 5, (SELECT id FROM categories WHERE name_hy = 'Սիրավեպ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մրրկահույզ լեռնանցք' AND author = 'Էմիլի Բրոնտե');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Gone with the Wind', 'Քամուց քշվածները', 'Մարգարեթ Միչել', 'Քաղաքացիական պատերազմի ֆոնին զարգացող սիրո և գոյատևման մեծ վեպ։', 7500, 6, (SELECT id FROM categories WHERE name_hy = 'Սիրավեպ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Քամուց քշվածները' AND author = 'Մարգարեթ Միչել');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Romeo and Juliet', 'Ռոմեո և Ջուլիետ', 'Ուիլյամ Շեքսպիր', 'Շեքսպիրի հայտնի ողբերգությունը երկու թշնամի ընտանիքների երիտասարդների սիրո մասին։', 2800, 19, (SELECT id FROM categories WHERE name_hy = 'Սիրավեպ' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ռոմեո և Ջուլիետ' AND author = 'Ուիլյամ Շեքսպիր');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Crime and Punishment', 'Ոճիր և պատիժ', 'Ֆյոդոր Դոստոևսկի', 'Ռասկոլնիկովի հոգեկան պայքարը՝ ոճրի, խղճի և քավության շուրջ։', 5200, 11, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ոճիր և պատիժ' AND author = 'Ֆյոդոր Դոստոևսկի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Brothers Karamazov', 'Եղբայրներ Կարամազովներ', 'Ֆյոդոր Դոստոևսկի', 'Փիլիսոփայական ու հոգեբանական վեպ՝ հավատի, ազատության և ընտանիքի մասին։', 7200, 6, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Եղբայրներ Կարամազովներ' AND author = 'Ֆյոդոր Դոստոևսկի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Anna Karenina', 'Աննա Կարենինա', 'Լև Տոլստոյ', 'Սիրո, հասարակական կարծիքի և անձնական ողբերգության մեծ վեպ։', 6900, 8, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Աննա Կարենինա' AND author = 'Լև Տոլստոյ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'War and Peace', 'Պատերազմ և խաղաղություն', 'Լև Տոլստոյ', 'Նապոլեոնյան պատերազմների ժամանակաշրջանի ռուսական հասարակության էպիկական պատկեր։', 9500, 4, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Պատերազմ և խաղաղություն' AND author = 'Լև Տոլստոյ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Les Misérables', 'Թշվառները', 'Վիկտոր Հյուգո', 'Ժան Վալժանի ճակատագրի շուրջ հյուսված մեծ վեպ՝ արդարության և ողորմածության մասին։', 8800, 7, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Թշվառները' AND author = 'Վիկտոր Հյուգո');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Hunchback of Notre-Dame', 'Նոտր Դամի տաճարը', 'Վիկտոր Հյուգո', 'Միջնադարյան Փարիզի ֆոնին տիրող կրքերի և մարդկային վեհության պատմություն։', 5600, 9, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Նոտր Դամի տաճարը' AND author = 'Վիկտոր Հյուգո');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Count of Monte Cristo', 'Մոնտե Կրիստո կոմս', 'Ալեքսանդր Դյումա', 'Անարդար բանտարկված երիտասարդի վրեժի ու վերածննդի արկածային պատմություն։', 8200, 10, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մոնտե Կրիստո կոմս' AND author = 'Ալեքսանդր Դյումա');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Three Musketeers', 'Երեք հրացանակիրները', 'Ալեքսանդր Դյումա', 'Դ’Արտանյանի և նրա ընկերների արկածները Ֆրանսիայի արքունիքում։', 5400, 13, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Երեք հրացանակիրները' AND author = 'Ալեքսանդր Դյումա');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'A Hero of Our Time', 'Մեր ժամանակի հերոսը', 'Միխայիլ Լերմոնտով', 'Պեչորինի բարդ կերպարի շուրջ կառուցված դասական ռուսական վեպ։', 3200, 8, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մեր ժամանակի հերոսը' AND author = 'Միխայիլ Լերմոնտով');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Eugene Onegin', 'Եվգենի Օնեգին', 'Ալեքսանդր Պուշկին', 'Չափածո վեպ՝ սիրո, ընկերության և կորսված հնարավորությունների մասին։', 3400, 7, (SELECT id FROM categories WHERE name_hy = 'Պոեզիա' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Եվգենի Օնեգին' AND author = 'Ալեքսանդր Պուշկին');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Hamlet', 'Համլետ', 'Ուիլյամ Շեքսպիր', 'Դանիայի արքայազնի ողբերգությունը՝ վրեժի, կասկածի և ճակատագրի մասին։', 2900, 16, (SELECT id FROM categories WHERE name_hy = 'Դասական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Համլետ' AND author = 'Ուիլյամ Շեքսպիր');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Treasure Island', 'Գանձերի կղզին', 'Ռոբերտ Լուիս Սթիվենսոն', 'Ծովահենների, քարտեզի և թաքնված գանձի մասին դասական արկածային վեպ։', 3300, 14, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Գանձերի կղզին' AND author = 'Ռոբերտ Լուիս Սթիվենսոն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Robinson Crusoe', 'Ռոբինզոն Կրուզո', 'Դանիել Դեֆո', 'Անբնակ կղզում մենակ մնացած նավաբեկյալի գոյատևման պատմությունը։', 3400, 11, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ռոբինզոն Կրուզո' AND author = 'Դանիել Դեֆո');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Journey to the Center of the Earth', 'Ճանապարհորդություն դեպի Երկրի կենտրոն', 'Ժյուլ Վեռն', 'Պրոֆեսորի և նրա եղբոր որդու արկածը՝ հրաբխի խառնարանից դեպի երկրի խորքը։', 3600, 9, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ճանապարհորդություն դեպի Երկրի կենտրոն' AND author = 'Ժյուլ Վեռն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Around the World in Eighty Days', 'Ութսուն օրում աշխարհի շուրջ', 'Ժյուլ Վեռն', 'Ֆիլեաս Ֆոգգի գրազը՝ շրջել աշխարհը ութսուն օրում։', 3500, 12, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ութսուն օրում աշխարհի շուրջ' AND author = 'Ժյուլ Վեռն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Adventures of Tom Sawyer', 'Թոմ Սոյերի արկածները', 'Մարկ Տվեն', 'Թոմի և նրա ընկերների զվարճալի ու վտանգավոր արկածները Միսիսիպիի ափին։', 3200, 15, (SELECT id FROM categories WHERE name_hy = 'Արկածային' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Թոմ Սոյերի արկածները' AND author = 'Մարկ Տվեն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Old Man and the Sea', 'Ծերունին և ծովը', 'Էռնեսթ Հեմինգուեյ', 'Ծեր ձկնորսի և հսկա ձկան մենամարտը բաց ծովում՝ տոկունության պատմություն։', 2800, 10, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ծերունին և ծովը' AND author = 'Էռնեսթ Հեմինգուեյ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'One Hundred Years of Solitude', 'Հարյուր տարվա մենություն', 'Գաբրիել Գարսիա Մարկես', 'Բուենդիա ընտանիքի յոթ սերունդների պատմությունը՝ կախարդական ռեալիզմի դասական օրինակ։', 6200, 7, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Հարյուր տարվա մենություն' AND author = 'Գաբրիել Գարսիա Մարկես');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Of Mice and Men', 'Մկների և մարդկանց մասին', 'Ջոն Սթայնբեկ', 'Երկու թափառաշրջիկ բանվորների ընկերության և երազանքի մասին կարճ վեպ։', 2900, 9, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մկների և մարդկանց մասին' AND author = 'Ջոն Սթայնբեկ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT '1984', '1984', 'Ջորջ Օրուել', 'Տոտալիտար հասարակության մռայլ պատկեր՝ հսկողության և ճշմարտության կորստի մասին։', 3900, 18, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = '1984' AND author = 'Ջորջ Օրուել');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Animal Farm', 'Անասնանոց', 'Ջորջ Օրուել', 'Ֆերմայի կենդանիների ապստամբության մասին այլաբանական պատմվածք։', 2600, 17, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Անասնանոց' AND author = 'Ջորջ Օրուել');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Fahrenheit 451', '451 աստիճան ըստ Ֆարենհայթի', 'Ռեյ Բրեդբերի', 'Ապագա, որտեղ գրքերն արգելված են, իսկ հրշեջները դրանք այրում են։', 3600, 6, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = '451 աստիճան ըստ Ֆարենհայթի' AND author = 'Ռեյ Բրեդբերի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Alchemist', 'Ալքիմիկոսը', 'Պաուլո Կոելյո', 'Երիտասարդ հովվի ճանապարհորդությունը՝ իր երազանքի և «անձնական առասպելի» որոնումով։', 3700, 21, (SELECT id FROM categories WHERE name_hy = 'Գեղարվեստական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ալքիմիկոսը' AND author = 'Պաուլո Կոելյո');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Little Prince', 'Փոքրիկ իշխանը', 'Անտուան դը Սենտ-Էքզյուպերի', 'Փոքրիկ իշխանի հանդիպումները տարբեր մոլորակներում՝ մեծերի և երեխաների համար։', 2900, 24, (SELECT id FROM categories WHERE name_hy = 'Մանկական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Փոքրիկ իշխանը' AND author = 'Անտուան դը Սենտ-Էքզյուպերի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Alice’s Adventures in Wonderland', 'Ալիսը Հրաշքների աշխարհում', 'Լյուիս Քերոլ', 'Աղջիկը ընկնում է նապաստակի փոսը և հայտնվում տարօրինակ ու զվարճալի աշխարհում։', 3100, 13, (SELECT id FROM categories WHERE name_hy = 'Մանկական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ալիսը Հրաշքների աշխարհում' AND author = 'Լյուիս Քերոլ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Pippi Longstocking', 'Փիփի Երկարագուլպան', 'Աստրիդ Լինդգրեն', 'Աշխարհի ամենաուժեղ ու անկախ աղջկա զվարճալի արկածները։', 3000, 10, (SELECT id FROM categories WHERE name_hy = 'Մանկական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Փիփի Երկարագուլպան' AND author = 'Աստրիդ Լինդգրեն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The Jungle Book', 'Ջունգլիների գիրքը', 'Ռադյարդ Քիփլինգ', 'Ջունգլիներում գայլերի մեջ մեծացած Մաուգլիի պատմությունը։', 3300, 8, (SELECT id FROM categories WHERE name_hy = 'Մանկական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ջունգլիների գիրքը' AND author = 'Ռադյարդ Քիփլինգ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Winnie-the-Pooh', 'Վինի Պուխ', 'Ալան Ալեքսանդր Միլն', 'Արջուկի և նրա ընկերների ջերմ ու զվարճալի պատմություններ։', 2700, 11, (SELECT id FROM categories WHERE name_hy = 'Մանկական' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Վինի Պուխ' AND author = 'Ալան Ալեքսանդր Միլն');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'How to Win Friends and Influence People', 'Ինչպես ձեռք բերել բարեկամներ և ազդել մարդկանց վրա', 'Դեյլ Կարնեգի', 'Հաղորդակցության և մարդկանց հետ հարաբերությունների դասական ձեռնարկ։', 3800, 19, (SELECT id FROM categories WHERE name_hy = 'Ինքնազարգացում' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ինչպես ձեռք բերել բարեկամներ և ազդել մարդկանց վրա' AND author = 'Դեյլ Կարնեգի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'The 7 Habits of Highly Effective People', 'Բարձր արդյունավետ մարդկանց 7 սովորությունները', 'Սթիվեն Քովի', 'Անձնական և մասնագիտական արդյունավետության սովորույթների համակարգ։', 5200, 10, (SELECT id FROM categories WHERE name_hy = 'Ինքնազարգացում' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Բարձր արդյունավետ մարդկանց 7 սովորությունները' AND author = 'Սթիվեն Քովի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Man’s Search for Meaning', 'Մարդը իմաստի որոնման մեջ', 'Վիկտոր Ֆրանկլ', 'Հոգեբանի հուշերը համակենտրոնացման ճամբարից և կյանքի իմաստի որոնումը։', 3500, 8, (SELECT id FROM categories WHERE name_hy = 'Ինքնազարգացում' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Մարդը իմաստի որոնման մեջ' AND author = 'Վիկտոր Ֆրանկլ');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Atomic Habits', 'Ատոմային սովորություններ', 'Ջեյմս Քլիր', 'Փոքր քայլերով սովորույթներ ձևավորելու և փոխելու գործնական մոտեցում։', 4600, 15, (SELECT id FROM categories WHERE name_hy = 'Ինքնազարգացում' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ատոմային սովորություններ' AND author = 'Ջեյմս Քլիր');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'Sapiens: A Brief History of Humankind', 'Սափիենս. Մարդկության համառոտ պատմություն', 'Յուվալ Նոյ Հարարի', 'Հայացք մարդկության զարգացմանը՝ նախնադարյան որսորդներից մինչև ժամանակակից հասարակություն։', 6500, 12, (SELECT id FROM categories WHERE name_hy = 'Գիտություն' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Սափիենս. Մարդկության համառոտ պատմություն' AND author = 'Յուվալ Նոյ Հարարի');
INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, rating, reviews_count)
SELECT 'A Brief History of Time', 'Ժամանակի համառոտ պատմություն', 'Սթիվեն Հոքինգ', 'Տիեզերքի ծագման, սև խոռոչների և ժամանակի բնույթի մասին գիտահանրամատչելի գիրք։', 4800, 9, (SELECT id FROM categories WHERE name_hy = 'Գիտություն' ORDER BY id LIMIT 1), '', 0, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title_hy = 'Ժամանակի համառոտ պատմություն' AND author = 'Սթիվեն Հոքինգ');

-- Կազմեր. գրքերին, որոնք կազմի նկար չունեն, տրվում է cover.php-ով գեներացվող կազմ (մինչև իրական նկար վերբեռնելը)
UPDATE books SET cover_image = CONCAT('cover.php?id=', id) WHERE cover_image IS NULL OR cover_image = '';
