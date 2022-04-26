-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2022 at 05:23 PM
-- Server version: 10.5.15-MariaDB-cll-lve
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
--
-- Dumping data for table `fruit_bays`
--

INSERT INTO `fruit_bays` (`id`, `fruit_bay_category_id`, `name`, `slug`, `description`, `price`, `image`, `created_at`, `updated_at`) VALUES
(3, 1, 'Coconut', 'coconut', 'Coconut is the fruit of the coconut palm (Cocos nucifera). It\'s used for its water, milk, oil, and tasty meat. Coconuts have been grown in tropical regions for more than 4,500 years but recently increased in popularity for their flavor, culinary uses, and potential health benefits', '400.00', 'public/uploads/images/1761475950_922287778.png', '2022-04-01 07:32:07', '2022-04-01 07:32:07'),
(4, 1, 'Orange', 'orange', 'Oranges are a type of low calorie, highly nutritious citrus fruit. As part of a healthful and varied diet, oranges contribute to strong, clear skin and can help lower a person’s risk of many conditions.', '105.00', 'public/uploads/images/1900868716_866963580.png', '2022-04-01 07:38:17', '2022-04-01 07:38:17'),
(5, 2, 'Apple', 'apple', 'Apples are a popular fruit, containing antioxidants, vitamins, dietary fiber, and a range of other nutrients. Due to their varied nutrient content, they may help prevent several health conditions.', '500.00', 'public/uploads/images/976783052_2013714617.jpg', '2022-04-01 14:37:51', '2022-04-01 14:37:51'),
(6, 1, 'Mango', 'mango', 'Mango trees are evergreen trees with a thick trunk and wide canopy. They can grow to a height of 100 feet or more with a canopy extending to about 35 feet or more, depending upon the climate and richness of the soil.\r\n\r\nThe leaves are leathery, lanceolate, and found in simple-alternate arrangement on the branches. They are dark green and about 5–16 inches in length.', '194.00', 'public/uploads/images/510622432_394180429.png', '2022-04-01 14:41:33', '2022-04-26 09:09:50'),
(7, 3, 'Pineapples', 'pineapples', 'Pineapples are tropical fruits that are rich in vitamins, enzymes and antioxidants. They may help boost the immune system, build strong bones and aid indigestion. Plus, despite their sweetness, pineapples are low in calories.', '500.00', 'public/uploads/images/1836800844_587173042.jpg', '2022-04-01 14:50:24', '2022-04-01 14:50:24'),
(8, 3, 'Guava', 'guava', 'Guavas are tropical trees originating in Central America. Their fruits are oval in shape with light green or yellow skin and contain edible seeds. What\'s more, guava leaves are used as an herbal tea and the leaf extract as a supplement. Guava fruits are amazingly rich in antioxidants, vitamin C, potassium, and fiber.', '1000.00', 'public/uploads/images/339385430_1913227851.jpg', '2022-04-01 14:58:40', '2022-04-01 14:58:40'),
(11, 1, 'Tengerine', 'tengerine1305370056', 'Tangerine is the common name for a widely cultivated variety of mandarin orange (Citrus reticulata), whose easily-separated fruit is characterized by a rind with a deep orange, red, or orange-red color. The term also refers to the fruit of this citrus plant.', '700.00', 'public/uploads/images/1333422717_415698319.jpg', '2022-04-01 15:04:29', '2022-04-01 15:04:29'),
(12, 3, 'Grape Fruit', 'grape-fruit', 'Grapefruit is a tropical citrus fruit known for its sweet yet tart taste. It is rich in nutrients, antioxidants, and fiber. This makes it one of the healthiest citrus fruits you can eat.\r\n\r\nPlus, research shows that grapefruit may have some powerful health benefits. These include weight loss and a reduced risk of heart disease.', '800.00', 'public/uploads/images/501224506_432780627.jpg', '2022-04-01 15:06:30', '2022-04-01 15:06:30'),
(13, 3, 'Paw Paw', 'paw-paw', 'Pawpaw (Asimina triloba) is a green, oval-shaped fruit that is harvested in the fall throughout the Eastern United States and Canada. It has a dull, often spotted outer skin with a soft, yellow interior that yields sweet custard-like flesh and large brown seeds. Many compare the taste and texture of the fruit to that of a banana or a mango.', '300.00', 'public/uploads/images/1926761881_510309168.jpg', '2022-04-01 15:09:29', '2022-04-01 15:09:29'),
(14, 3, 'Watermelon', 'watermelon', 'Watermelon is a sweet and refreshing low calorie summer snack. It provides hydration and also essential nutrients, including vitamins, minerals, and antioxidants.', '800.00', 'public/uploads/images/968904126_218985317.jpg', '2022-04-01 15:11:50', '2022-04-01 15:11:50'),
(15, 1, 'Carrots', 'carrots', 'Carrots are rich in vitamins, minerals, and antioxidant compounds. As part of a balanced diet, they can help support immune function, reduce the risk of some cancers and promote wound healing and digestive health.', '500.00', 'public/uploads/images/29610101_1092010367.png', '2022-04-01 15:14:08', '2022-04-26 09:12:51'),
(16, 2, 'Tomato', 'tomato', 'Tomatoes are fruits that are considered vegetables by nutritionists. Botanically, a fruit is a ripened flower ovary and contains seeds. Tomatoes, plums, zucchinis, and melons are all edible fruits, but things like maple “helicopters” and floating dandelion puffs are fruits too.', '300.00', 'public/uploads/images/1628050630_345327794.jpg', '2022-04-01 15:19:42', '2022-04-01 15:19:42'),
(17, 3, 'Red pepper', 'red-pepper', 'Red pepper—also called bell pepper, red bell pepper, capsicum, or sweet pepper—has a mildly sweet yet earthy taste. These peppers are fully mature versions of the more bitter green bell peppers.', '400.00', 'public/uploads/images/97853645_1511776984.jpg', '2022-04-01 15:21:39', '2022-04-01 15:21:39'),
(18, 2, 'Green pepper', 'green-pepper', 'Green sweet peppers or bell peppers (Capsicum annuum) are commonly thought of as vegetables, though they’re technically a type of fruit (1Trusted Source).\r\n\r\nBell peppers have thick walls, are bell-shaped, and come in a variety of colors, including red, yellow, and purple.', '297.00', 'public/uploads/images/404079393_1295561898.jpg', '2022-04-01 15:24:03', '2022-04-01 15:24:03'),
(19, 2, 'Onion', 'onion', 'Onions belong to the Allium family of plants, which also includes chives, garlic, and leeks. These vegetables have characteristic pungent flavors and some medicinal properties.', '300.00', 'public/uploads/images/1296719327_2785857.jpg', '2022-04-01 15:26:22', '2022-04-01 15:26:22'),
(20, 3, 'Lettuce', 'lettuce', 'lettuce, (Lactuca sativa), annual leaf vegetable of the aster family (Asteraceae). Most lettuce varieties are eaten fresh and are commonly served as the base of green salads. Lettuce is generally a rich source of vitamins K and A, though the nutritional quality varies, depending on the variety.', '400.00', 'public/uploads/images/1365061454_1578163112.jpg', '2022-04-01 15:27:54', '2022-04-01 15:27:54'),
(21, 3, 'Cabbage', 'cabbage', 'Cabbage, which is often lumped into the same category as lettuce because of their similar appearance, is actually a part of the cruciferous vegetable family.', '400.00', NULL, '2022-04-01 15:30:01', '2022-04-01 15:30:01'),
(22, 3, 'Carbage', 'carbage', 'Cabbage, which is often lumped into the same category as lettuce because of their similar appearance, is actually a part of the cruciferous vegetable family.', '605.00', 'public/uploads/images/55988263_1913635158.jpg', '2022-04-01 15:30:46', '2022-04-01 15:30:46'),
(23, 2, 'Strawberries', 'strawberries', 'Strawberries, like other berries, are rich in vitamins, minerals, fiber, and compounds with antioxidant and anti-inflammatory properties. As part of a nutritious diet, they can help prevent various conditions.', '800.00', NULL, '2022-04-01 15:33:01', '2022-04-01 15:33:01'),
(24, 2, 'Garden Egg', 'garden-egg', 'Garden egg is a type of eggplant that is used as a food crop in several countries in Africa. It is a small, white fruit with a teardrop or roundish shape that is valued for its bitterness. There is debate on the specific species of garden egg.', '350.00', 'public/uploads/images/1790723079_42734176.jpg', '2022-04-01 15:40:48', '2022-04-01 15:40:48'),
(25, 3, 'Green peas', 'green-peas', 'Green peas, or “garden peas,” are the small, spherical seeds that come from pods produced by the Pisum sativum plant. They have been part of the human diet for hundreds of years and are consumed all over the world. Strictly speaking, green peas are not vegetables.', '500.00', 'public/uploads/images/181838812_603031183.jpg', '2022-04-01 15:43:04', '2022-04-01 15:43:04'),
(26, 3, 'Green Beans', 'green-beans', 'Also known as runner bean, snap bean, or string bean, a Green Beans is a long and slender vegetable with green pods. There are numerous varieties of Green Beans that all appear the same but differ in width, length and thickness.', '400.00', 'public/uploads/images/284863348_506731089.jpg', '2022-04-01 15:48:19', '2022-04-01 15:48:19'),
(27, 1, 'Lime', 'lime', 'Limes are sour, round, and bright green citrus fruits. They\'re nutritional powerhouses — high in vitamin C, antioxidants, and other nutrients. There are many species of limes, including the Key lime (Citrus aurantifolia), Persian lime (Citrus latifolia), desert lime (Citrus glauca), and makrut lime (Citrus hystrix).', '600.00', 'public/uploads/images/639336619_173603523.jpg', '2022-04-01 15:49:58', '2022-04-01 15:49:58');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
