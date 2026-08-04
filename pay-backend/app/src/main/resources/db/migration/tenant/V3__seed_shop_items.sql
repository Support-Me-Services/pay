-- Stan finalny po dwóch migracjach seedujących Laravela (insert + backfill
-- cen/opisów) — wstawiamy od razu docelowe wartości. user_id zostaje NULL tu;
-- przypisanie do właściciela (lula-marcin) odbywa się w warstwie seedowania
-- danych deweloperskich, nie w migracji schematu.

INSERT INTO shop_items (slug, name, image, min_amount, price, description, is_default, tag_uid, active, sort) VALUES
    ('serduszko', 'Serduszko', 'img/sklep/heart-wesprzyj.svg', 900, 900, 'Naklejka NFC „Serduszko” — przyklej i przyjmuj wsparcie jednym zbliżeniem telefonu. Programowalny tag NFC w formie serca.', true, NULL, true, 0),
    ('kubek-supportme', 'Kubek SupportMe', 'img/sklep/kubek.jpg', 3900, 3900, 'Ceramiczny kubek SupportMe 330 ml z nadrukiem logo. Nadaje się do zmywarki i mikrofalówki.', false, NULL, true, 1),
    ('koszulka-supportme', 'Koszulka SupportMe', 'img/sklep/koszulka.jpg', 6900, 6900, 'Bawełniana koszulka SupportMe (100% bawełna organiczna) z nadrukiem logo. Dostępne rozmiary S-XXL.', false, NULL, true, 2),
    ('pin-supportme', 'Pin SupportMe', 'img/sklep/pin.jpg', 1500, 1500, 'Metalowa przypinka (pin) SupportMe z zapięciem motylkowym. Średnica 25 mm.', false, NULL, true, 3),
    ('brelok-supportme', 'Brelok SupportMe', 'img/sklep/brelok.jpg', 1900, 1900, 'Brelok NFC SupportMe — programowalny tag zbliżeniowy w obudowie z brelokiem do kluczy.', false, NULL, true, 4);
