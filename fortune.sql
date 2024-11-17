CREATE TABLE `items`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_items` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

INSERT INTO `items` VALUES (1, 49623, 'Темная Скорбь');
INSERT INTO `items` VALUES (2, 50675, 'Перчатки скрытности Алдрианы');
INSERT INTO `items` VALUES (3, 35356, 'Перчатки из драконьей шкуры');
INSERT INTO `items` VALUES (4, 36942, 'Ледяная Скорбь');

CREATE TABLE `sent_items`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_name` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 0 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;
