CREATE TABLE `items`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_items` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

INSERT INTO `items` VALUES (1, 49623, 'Темная Скорбь', '1.jpg');
INSERT INTO `items` VALUES (2, 50675, 'Перчатки скрытности Алдрианы', '2.jpg');
INSERT INTO `items` VALUES (3, 35356, 'Перчатки из драконьей шкуры', '3.jpg');
INSERT INTO `items` VALUES (4, 36942, 'Ледяная Скорбь', '4.jpg');
INSERT INTO `items` VALUES (5, 39769, 'Арканитовый потрошитель', '5.jpg');
INSERT INTO `items` VALUES (6, 43601, 'Бруннхильдарский гигантский топор\r\n', '6.jpg');
INSERT INTO `items` VALUES (7, 47069, 'Творец справедливости', '7.jpg');
INSERT INTO `items` VALUES (8, 30316, 'Сокрушитель', '8.jpg');

CREATE TABLE `sent_items`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_name` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 0 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;
