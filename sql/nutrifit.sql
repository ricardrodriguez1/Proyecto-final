-- ============================================
-- NutriFit — Database Schema & Seed Data
-- ============================================

DROP DATABASE IF EXISTS nutrifit;
CREATE DATABASE nutrifit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nutrifit;

-- ============================================
-- TABLES
-- ============================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) DEFAULT '',
    prep_time INT DEFAULT 0 COMMENT 'minutes',
    cook_time INT DEFAULT 0 COMMENT 'minutes',
    servings INT DEFAULT 1,
    difficulty ENUM('facil', 'media', 'dificil') DEFAULT 'facil',
    category ENUM('desayuno', 'comida', 'cena', 'snack') DEFAULT 'comida',
    diet_type ENUM('omnivoro', 'vegetariano', 'vegano') DEFAULT 'omnivoro',
    calories INT DEFAULT 0,
    protein DECIMAL(6,1) DEFAULT 0,
    carbs DECIMAL(6,1) DEFAULT 0,
    fat DECIMAL(6,1) DEFAULT 0,
    fiber DECIMAL(6,1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE recipe_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    ingredient_name VARCHAR(150) NOT NULL,
    quantity VARCHAR(50) DEFAULT '',
    unit VARCHAR(30) DEFAULT '',
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE recipe_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    step_number INT NOT NULL,
    instruction TEXT NOT NULL,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE recipe_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index for fast filtering
CREATE INDEX idx_recipes_category ON recipes(category);
CREATE INDEX idx_recipes_diet_type ON recipes(diet_type);
CREATE INDEX idx_recipes_calories ON recipes(calories);
CREATE INDEX idx_recipe_tags_tag ON recipe_tags(tag);

CREATE TABLE user_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    peso DECIMAL(5,1) NOT NULL,
    altura DECIMAL(5,1) NOT NULL,
    edad INT NOT NULL,
    genero VARCHAR(10) NOT NULL,
    actividad VARCHAR(50) NOT NULL,
    objetivo VARCHAR(50) NOT NULL,
    diet_type VARCHAR(50) NOT NULL,
    target_calories INT NOT NULL,
    protein INT NOT NULL,
    carbs INT NOT NULL,
    fat INT NOT NULL,
    plan_type ENUM('completo', 'receta') NOT NULL,
    breakfast_id INT DEFAULT NULL,
    lunch_id INT DEFAULT NULL,
    dinner_id INT DEFAULT NULL,
    snack_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (breakfast_id) REFERENCES recipes(id) ON DELETE SET NULL,
    FOREIGN KEY (lunch_id) REFERENCES recipes(id) ON DELETE SET NULL,
    FOREIGN KEY (dinner_id) REFERENCES recipes(id) ON DELETE SET NULL,
    FOREIGN KEY (snack_id) REFERENCES recipes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- SEED USERS (passwords: admin123 / user123)
-- ============================================

INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@nutrifit.com', '$2y$10$YEi3p/sOqLSHqGqfjlHfCewuAeHBNqcMqJxKn5C5GzXbB2JGKyLZe', 'admin'),
('usuario', 'usuario@nutrifit.com', '$2y$10$A.jn0RX1qB0dFv8GvDxJwO4a6t1soEJOGHpVGjN0pMmzH0D5HVxOK', 'user');

-- ============================================
-- SEED RECIPES
-- ============================================

-- === DESAYUNO (1-12) ===
INSERT INTO recipes (id,title,description,image_url,prep_time,cook_time,servings,difficulty,category,diet_type,calories,protein,carbs,fat,fiber) VALUES
(1,'Tostada de Aguacate con Huevo','Tostada integral con aguacate cremoso, huevo pochado y semillas de sésamo. Un desayuno nutritivo y energético.','https://images.unsplash.com/photo-1525351484163-7529414344d8?w=600',10,5,1,'facil','desayuno','vegetariano',350,18.0,28.0,20.0,7.0),
(2,'Porridge de Avena con Frutas','Avena cocida con leche vegetal, plátano, arándanos y un toque de canela. Energía natural para empezar el día.','https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=600',5,10,1,'facil','desayuno','vegano',280,8.0,52.0,5.0,6.0),
(3,'Tortilla Francesa con Queso','Tortilla esponjosa de dos huevos con queso emmental fundido y finas hierbas.','https://images.unsplash.com/photo-1510693206972-df098062cb71?w=600',5,5,1,'facil','desayuno','vegetariano',320,22.0,2.0,25.0,0.0),
(4,'Smoothie Bowl de Açaí','Bowl cremoso de açaí con granola, rodajas de plátano, coco rallado y semillas de chía.','https://images.unsplash.com/photo-1590301157890-4810ed352733?w=600',10,0,1,'facil','desayuno','vegano',310,6.0,48.0,12.0,8.0),
(5,'Huevos Revueltos con Espinacas','Huevos revueltos suaves con espinacas frescas, tomate cherry y pimienta negra.','https://images.unsplash.com/photo-1482049016688-2d3e1b311543?w=600',5,5,1,'facil','desayuno','vegetariano',250,18.0,4.0,18.0,2.0),
(6,'Pancakes de Avena y Plátano','Tortitas saludables de avena y plátano sin azúcar añadido. Acompañadas de frutos rojos.','https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600',10,10,2,'facil','desayuno','vegetariano',380,12.0,62.0,9.0,5.0),
(7,'Yogur Griego con Granola','Yogur griego natural con granola casera, miel y frutas de temporada. Rico en proteínas.','https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600',5,0,1,'facil','desayuno','vegetariano',290,15.0,38.0,10.0,3.0),
(8,'Tostada Integral con Tomate','Pan integral con tomate rallado, aceite de oliva virgen extra y jamón serrano. Desayuno mediterráneo.','https://images.unsplash.com/photo-1528207776546-365bb710ee93?w=600',5,3,1,'facil','desayuno','omnivoro',270,14.0,30.0,10.0,4.0),
(9,'Batido de Proteínas con Fresas','Batido cremoso de fresas con proteína de suero, leche de almendras y un toque de vainilla.','https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=600',5,0,1,'facil','desayuno','vegetariano',220,25.0,18.0,5.0,2.0),
(10,'Gachas de Avena con Canela','Avena cocida estilo tradicional con canela, manzana troceada y nueces. Reconfortante y nutritivo.','https://images.unsplash.com/photo-1495214783159-3503fd1b572d?w=600',5,10,1,'facil','desayuno','vegano',260,7.0,46.0,6.0,5.0),
(11,'Wrap Integral de Pavo y Aguacate','Wrap de tortilla integral relleno de pavo, aguacate, lechuga y tomate. Perfecto para llevar.','https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=600',10,0,1,'facil','desayuno','omnivoro',340,24.0,30.0,14.0,6.0),
(12,'Bol de Frutas con Chía','Bol colorido de frutas frescas de temporada con semillas de chía y zumo de naranja natural.','https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?w=600',10,0,1,'facil','desayuno','vegano',200,4.0,40.0,4.0,8.0);

-- === COMIDA (13-27) ===
INSERT INTO recipes (id,title,description,image_url,prep_time,cook_time,servings,difficulty,category,diet_type,calories,protein,carbs,fat,fiber) VALUES
(13,'Pollo a la Plancha con Ensalada','Pechuga de pollo a la plancha con ensalada mixta, tomate cherry, pepino y vinagreta de limón.','https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=600',10,15,1,'facil','comida','omnivoro',420,42.0,12.0,22.0,4.0),
(14,'Pasta Integral con Verduras','Pasta integral salteada con calabacín, pimiento rojo, champiñones y salsa de tomate casera.','https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=600',10,15,2,'facil','comida','vegano',480,14.0,78.0,10.0,8.0),
(15,'Salmón al Horno con Brócoli','Filete de salmón al horno con brócoli al vapor, patatas baby y salsa de eneldo.','https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=600',10,25,1,'media','comida','omnivoro',450,38.0,22.0,24.0,5.0),
(16,'Ensalada César con Pollo','Ensalada César clásica con pollo a la plancha, crutones integrales, parmesano y salsa César ligera.','https://images.unsplash.com/photo-1550304943-4f24f54ddde9?w=600',15,10,1,'facil','comida','omnivoro',380,32.0,18.0,22.0,3.0),
(17,'Buddha Bowl de Quinoa','Bowl nutritivo de quinoa con garbanzos asados, aguacate, zanahoria, edamame y tahini.','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600',15,20,1,'media','comida','vegano',410,16.0,52.0,18.0,10.0),
(18,'Arroz Integral con Pollo','Arroz integral salteado con pollo, guisantes, zanahoria y salsa de soja baja en sodio.','https://images.unsplash.com/photo-1516714435131-44d6b64dc6a2?w=600',10,25,2,'facil','comida','omnivoro',520,35.0,68.0,10.0,4.0),
(19,'Lentejas Estofadas','Guiso tradicional de lentejas con verduras, pimentón y laurel. Reconfortante y rico en hierro.','https://images.unsplash.com/photo-1547592166-23ac45744aec?w=600',10,40,4,'facil','comida','vegano',380,22.0,58.0,4.0,16.0),
(20,'Pechuga de Pavo con Patatas','Pechuga de pavo a la plancha con patatas asadas al romero y ensalada verde.','https://images.unsplash.com/photo-1432139509613-5c4255a1d197?w=600',10,30,1,'facil','comida','omnivoro',460,40.0,38.0,14.0,4.0),
(21,'Ensalada de Garbanzos','Ensalada mediterránea de garbanzos con pepino, tomate, cebolla morada, aceitunas y vinagreta.','https://images.unsplash.com/photo-1511690743698-d9d18f7e20f1?w=600',15,0,2,'facil','comida','vegano',350,14.0,48.0,12.0,10.0),
(22,'Wok de Tofu con Verduras','Tofu firme salteado al wok con brócoli, pimiento, zanahoria y salsa teriyaki casera.','https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600',15,15,2,'media','comida','vegano',340,22.0,30.0,16.0,6.0),
(23,'Merluza a la Plancha','Filete de merluza a la plancha con ensalada templada de pimientos asados y aceite de oliva.','https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600',10,12,1,'facil','comida','omnivoro',320,34.0,8.0,16.0,2.0),
(24,'Risotto de Champiñones','Arroz arborio cremoso con champiñones variados, parmesano y un toque de trufa.','https://images.unsplash.com/photo-1476124369491-e7addf5db371?w=600',10,30,2,'media','comida','vegetariano',450,12.0,62.0,16.0,3.0),
(25,'Fajitas de Pollo','Fajitas de pollo con pimientos de colores, cebolla caramelizada y especias mexicanas.','https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600',15,15,2,'facil','comida','omnivoro',490,36.0,40.0,20.0,5.0),
(26,'Crema de Calabaza','Crema suave de calabaza asada con jengibre, leche de coco y semillas de calabaza tostadas.','https://images.unsplash.com/photo-1476718406336-bb5a9690ee2a?w=600',10,25,3,'facil','comida','vegano',220,5.0,34.0,8.0,4.0),
(27,'Poké Bowl de Salmón','Bowl hawaiano con arroz de sushi, salmón fresco, aguacate, edamame, mango y salsa ponzu.','https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600',20,10,1,'media','comida','omnivoro',530,32.0,58.0,20.0,6.0);

-- === CENA (28-42) ===
INSERT INTO recipes (id,title,description,image_url,prep_time,cook_time,servings,difficulty,category,diet_type,calories,protein,carbs,fat,fiber) VALUES
(28,'Sopa de Verduras Casera','Sopa reconfortante de verduras de temporada con patata, zanahoria, puerro y apio.','https://images.unsplash.com/photo-1547592166-23ac45744aec?w=600',15,30,4,'facil','cena','vegano',180,5.0,32.0,3.0,6.0),
(29,'Tortilla de Patatas','Tortilla española clásica con patatas, cebolla caramelizada y huevos de corral.','https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600',15,20,4,'media','cena','vegetariano',350,14.0,30.0,20.0,3.0),
(30,'Revuelto de Setas y Espárragos','Revuelto suave de huevo con setas de temporada y espárragos trigueros salteados.','https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600',10,10,1,'facil','cena','vegetariano',220,16.0,6.0,16.0,3.0),
(31,'Pechuga de Pollo al Limón','Pechuga de pollo marinada al limón con hierbas aromáticas y verduras al vapor.','https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=600',10,15,1,'facil','cena','omnivoro',280,36.0,6.0,12.0,2.0),
(32,'Ensalada Templada de Quinoa','Quinoa templada con espinacas, tomate seco, aceitunas kalamata y queso feta.','https://images.unsplash.com/photo-1505576399279-565b52d4ac71?w=600',10,15,1,'facil','cena','vegetariano',300,12.0,38.0,12.0,5.0),
(33,'Lubina al Horno','Lubina entera al horno con patatas panadera, cebolla y pimiento. Plato mediterráneo tradicional.','https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600',15,30,2,'media','cena','omnivoro',310,32.0,18.0,12.0,2.0),
(34,'Hamburguesa de Lentejas','Hamburguesa vegetal casera de lentejas con avena, especias y salsa de yogur vegano.','https://images.unsplash.com/photo-1520072959219-c595e6cdc07a?w=600',20,15,2,'media','cena','vegano',320,16.0,42.0,10.0,8.0),
(35,'Crema de Brócoli','Crema suave de brócoli con patata, puerro y un toque de nuez moscada. Ligera y nutritiva.','https://images.unsplash.com/photo-1547592180-85f173990554?w=600',10,20,3,'facil','cena','vegano',210,8.0,30.0,6.0,6.0),
(36,'Sardinas a la Plancha','Sardinas frescas a la plancha con ensalada de tomate, pepino y cebolla. Rica en omega-3.','https://images.unsplash.com/photo-1485921325833-c519f76c4927?w=600',10,10,1,'facil','cena','omnivoro',290,28.0,4.0,18.0,1.0),
(37,'Calabacines Rellenos','Calabacines rellenos de verduras salteadas, quinoa y salsa de tomate. Ligeros y saciantes.','https://images.unsplash.com/photo-1564834724105-918b73d1b9e0?w=600',20,25,2,'media','cena','vegano',250,10.0,36.0,8.0,6.0),
(38,'Wrap de Pollo con Hummus','Wrap integral con pollo desmenuzado, hummus, lechuga, tomate y zanahoria rallada.','https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=600',10,10,1,'facil','cena','omnivoro',360,30.0,32.0,14.0,5.0),
(39,'Gazpacho Andaluz','Gazpacho tradicional con tomate, pimiento, pepino, ajo y aceite de oliva. Refrescante y ligero.','https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=600',15,0,4,'facil','cena','vegano',150,3.0,14.0,10.0,3.0),
(40,'Bacalao con Pisto','Lomo de bacalao al horno sobre pisto manchego de calabacín, pimiento, tomate y cebolla.','https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600',15,25,2,'media','cena','omnivoro',330,32.0,16.0,16.0,4.0),
(41,'Huevos al Horno con Espinacas','Huevos al horno sobre cama de espinacas con tomate y queso gratinado. Sencillo y delicioso.','https://images.unsplash.com/photo-1482049016688-2d3e1b311543?w=600',5,15,1,'facil','cena','vegetariano',270,18.0,8.0,18.0,3.0),
(42,'Ensalada de Pasta Fría','Pasta corta integral con atún, maíz, tomate cherry, aceitunas y vinagreta de mostaza.','https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=600',15,10,2,'facil','cena','omnivoro',340,18.0,48.0,10.0,4.0);

-- === SNACK (43-50) ===
INSERT INTO recipes (id,title,description,image_url,prep_time,cook_time,servings,difficulty,category,diet_type,calories,protein,carbs,fat,fiber) VALUES
(43,'Hummus con Crudités','Hummus casero de garbanzos con palitos de zanahoria, pepino y pimiento rojo.','https://images.unsplash.com/photo-1577805947697-89e18249d767?w=600',10,0,2,'facil','snack','vegano',150,6.0,18.0,6.0,5.0),
(44,'Yogur de Soja con Nueces','Yogur vegetal de soja con nueces troceadas, semillas de lino y un toque de agave.','https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600',5,0,1,'facil','snack','vegano',180,8.0,16.0,10.0,2.0),
(45,'Barritas Energéticas Caseras','Barritas de avena, dátiles, mantequilla de cacahuete y pepitas de chocolate negro.','https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600',15,0,6,'facil','snack','vegano',200,6.0,28.0,8.0,3.0),
(46,'Tostada de Queso Fresco con Tomate','Pan integral tostado con queso fresco, rodajas de tomate, orégano y aceite de oliva.','https://images.unsplash.com/photo-1528207776546-365bb710ee93?w=600',5,3,1,'facil','snack','vegetariano',160,8.0,18.0,6.0,2.0),
(47,'Mix de Frutos Secos','Mezcla energética de almendras, nueces, anacardos, pasas y arándanos deshidratados.','https://images.unsplash.com/photo-1599599810769-bcde5a160d32?w=600',5,0,3,'facil','snack','vegano',220,6.0,20.0,14.0,3.0),
(48,'Batido Verde Detox','Batido de espinacas, manzana verde, jengibre, limón y pepino. Depurativo y refrescante.','https://images.unsplash.com/photo-1610970881699-44a5587cabec?w=600',5,0,1,'facil','snack','vegano',130,3.0,26.0,1.0,4.0),
(49,'Guacamole con Nachos','Guacamole cremoso con aguacate, tomate, cebolla, cilantro y lima. Con nachos integrales.','https://images.unsplash.com/photo-1615870216519-2f9fa575fa5c?w=600',10,0,3,'facil','snack','vegano',250,4.0,28.0,14.0,6.0),
(50,'Manzana con Mantequilla de Cacahuete','Rodajas de manzana con mantequilla de cacahuete natural y un toque de canela.','https://images.unsplash.com/photo-1568702846914-96b305d2aaeb?w=600',5,0,1,'facil','snack','vegano',190,5.0,24.0,10.0,4.0);

-- ============================================
-- SEED INGREDIENTS (3-5 per recipe)
-- ============================================

INSERT INTO recipe_ingredients (recipe_id, ingredient_name, quantity, unit) VALUES
-- 1. Tostada de Aguacate
(1,'Pan integral','2','rebanadas'),(1,'Aguacate','1','unidad'),(1,'Huevo','1','unidad'),(1,'Semillas de sésamo','1','cucharadita'),(1,'Sal y pimienta','','al gusto'),
-- 2. Porridge
(2,'Copos de avena','60','g'),(2,'Leche vegetal','200','ml'),(2,'Plátano','1','unidad'),(2,'Arándanos','50','g'),(2,'Canela','1','pizca'),
-- 3. Tortilla
(3,'Huevos','2','unidades'),(3,'Queso emmental','30','g'),(3,'Mantequilla','5','g'),(3,'Finas hierbas','1','pizca'),
-- 4. Smoothie Bowl
(4,'Pulpa de açaí','100','g'),(4,'Plátano congelado','1','unidad'),(4,'Granola','30','g'),(4,'Coco rallado','10','g'),(4,'Semillas de chía','1','cucharada'),
-- 5. Huevos Revueltos
(5,'Huevos','3','unidades'),(5,'Espinacas frescas','60','g'),(5,'Tomate cherry','6','unidades'),(5,'Aceite de oliva','1','cucharada'),
-- 6. Pancakes
(6,'Copos de avena','100','g'),(6,'Plátano maduro','1','unidad'),(6,'Huevo','1','unidad'),(6,'Frutos rojos','80','g'),
-- 7. Yogur Griego
(7,'Yogur griego natural','200','g'),(7,'Granola','40','g'),(7,'Miel','1','cucharada'),(7,'Frutas de temporada','80','g'),
-- 8. Tostada con Tomate
(8,'Pan integral','2','rebanadas'),(8,'Tomate maduro','1','unidad'),(8,'Aceite de oliva virgen','1','cucharada'),(8,'Jamón serrano','40','g'),
-- 9. Batido de Proteínas
(9,'Proteína de suero','30','g'),(9,'Fresas','100','g'),(9,'Leche de almendras','250','ml'),(9,'Extracto de vainilla','1','cucharadita'),
-- 10. Gachas de Avena
(10,'Copos de avena','60','g'),(10,'Agua','250','ml'),(10,'Manzana','1','unidad'),(10,'Nueces','20','g'),(10,'Canela','1','cucharadita'),
-- 11. Wrap de Pavo
(11,'Tortilla integral','1','unidad'),(11,'Pechuga de pavo','60','g'),(11,'Aguacate','0.5','unidad'),(11,'Lechuga','2','hojas'),(11,'Tomate','0.5','unidad'),
-- 12. Bol de Frutas
(12,'Frutas variadas','200','g'),(12,'Semillas de chía','2','cucharadas'),(12,'Zumo de naranja','100','ml'),
-- 13. Pollo a la Plancha
(13,'Pechuga de pollo','200','g'),(13,'Mezcla de lechugas','80','g'),(13,'Tomate cherry','8','unidades'),(13,'Pepino','0.5','unidad'),(13,'Aceite de oliva','1','cucharada'),
-- 14. Pasta con Verduras
(14,'Pasta integral','160','g'),(14,'Calabacín','1','unidad'),(14,'Pimiento rojo','1','unidad'),(14,'Champiñones','100','g'),(14,'Salsa de tomate','150','ml'),
-- 15. Salmón al Horno
(15,'Filete de salmón','180','g'),(15,'Brócoli','150','g'),(15,'Patatas baby','100','g'),(15,'Eneldo','1','cucharada'),(15,'Limón','0.5','unidad'),
-- 16. Ensalada César
(16,'Pechuga de pollo','150','g'),(16,'Lechuga romana','1','unidad'),(16,'Crutones integrales','30','g'),(16,'Parmesano','20','g'),(16,'Salsa César ligera','2','cucharadas'),
-- 17. Buddha Bowl
(17,'Quinoa','80','g'),(17,'Garbanzos cocidos','100','g'),(17,'Aguacate','0.5','unidad'),(17,'Zanahoria','1','unidad'),(17,'Tahini','1','cucharada'),
-- 18. Arroz con Pollo
(18,'Arroz integral','160','g'),(18,'Pechuga de pollo','200','g'),(18,'Guisantes','60','g'),(18,'Zanahoria','1','unidad'),(18,'Salsa de soja','2','cucharadas'),
-- 19. Lentejas Estofadas
(19,'Lentejas','200','g'),(19,'Zanahoria','2','unidades'),(19,'Patata','1','unidad'),(19,'Pimentón','1','cucharadita'),(19,'Laurel','2','hojas'),
-- 20. Pavo con Patatas
(20,'Pechuga de pavo','200','g'),(20,'Patatas','200','g'),(20,'Romero','1','rama'),(20,'Ensalada verde','60','g'),
-- 21. Ensalada de Garbanzos
(21,'Garbanzos cocidos','200','g'),(21,'Pepino','1','unidad'),(21,'Tomate','1','unidad'),(21,'Cebolla morada','0.5','unidad'),(21,'Aceitunas negras','30','g'),
-- 22. Wok de Tofu
(22,'Tofu firme','200','g'),(22,'Brócoli','100','g'),(22,'Pimiento rojo','1','unidad'),(22,'Zanahoria','1','unidad'),(22,'Salsa teriyaki','3','cucharadas'),
-- 23. Merluza
(23,'Filete de merluza','200','g'),(23,'Pimiento rojo asado','1','unidad'),(23,'Aceite de oliva','2','cucharadas'),(23,'Limón','0.5','unidad'),
-- 24. Risotto
(24,'Arroz arborio','160','g'),(24,'Champiñones variados','150','g'),(24,'Parmesano','30','g'),(24,'Caldo de verduras','500','ml'),(24,'Aceite de trufa','1','cucharadita'),
-- 25. Fajitas
(25,'Pechuga de pollo','200','g'),(25,'Pimientos de colores','2','unidades'),(25,'Cebolla','1','unidad'),(25,'Tortillas de trigo','3','unidades'),(25,'Especias mexicanas','2','cucharaditas'),
-- 26. Crema de Calabaza
(26,'Calabaza','400','g'),(26,'Jengibre fresco','10','g'),(26,'Leche de coco','100','ml'),(26,'Semillas de calabaza','15','g'),
-- 27. Poké Bowl
(27,'Arroz de sushi','120','g'),(27,'Salmón fresco','120','g'),(27,'Aguacate','0.5','unidad'),(27,'Edamame','50','g'),(27,'Salsa ponzu','2','cucharadas'),
-- 28. Sopa de Verduras
(28,'Patata','1','unidad'),(28,'Zanahoria','2','unidades'),(28,'Puerro','1','unidad'),(28,'Apio','2','ramas'),(28,'Caldo de verduras','750','ml'),
-- 29. Tortilla de Patatas
(29,'Patatas','300','g'),(29,'Huevos','4','unidades'),(29,'Cebolla','1','unidad'),(29,'Aceite de oliva','100','ml'),
-- 30. Revuelto de Setas
(30,'Huevos','2','unidades'),(30,'Setas variadas','150','g'),(30,'Espárragos trigueros','6','unidades'),(30,'Aceite de oliva','1','cucharada'),
-- 31. Pollo al Limón
(31,'Pechuga de pollo','200','g'),(31,'Limón','1','unidad'),(31,'Hierbas aromáticas','1','cucharada'),(31,'Verduras al vapor','150','g'),
-- 32. Ensalada de Quinoa
(32,'Quinoa','80','g'),(32,'Espinacas','60','g'),(32,'Tomate seco','30','g'),(32,'Aceitunas kalamata','20','g'),(32,'Queso feta','40','g'),
-- 33. Lubina
(33,'Lubina','400','g'),(33,'Patatas','200','g'),(33,'Cebolla','1','unidad'),(33,'Pimiento verde','1','unidad'),
-- 34. Hamburguesa de Lentejas
(34,'Lentejas cocidas','200','g'),(34,'Copos de avena','40','g'),(34,'Cebolla','0.5','unidad'),(34,'Especias','1','cucharada'),
-- 35. Crema de Brócoli
(35,'Brócoli','300','g'),(35,'Patata','1','unidad'),(35,'Puerro','1','unidad'),(35,'Nuez moscada','1','pizca'),
-- 36. Sardinas
(36,'Sardinas frescas','200','g'),(36,'Tomate','1','unidad'),(36,'Pepino','0.5','unidad'),(36,'Aceite de oliva','1','cucharada'),
-- 37. Calabacines Rellenos
(37,'Calabacines','2','unidades'),(37,'Quinoa','60','g'),(37,'Verduras variadas','100','g'),(37,'Salsa de tomate','100','ml'),
-- 38. Wrap de Pollo
(38,'Tortilla integral','1','unidad'),(38,'Pollo desmenuzado','100','g'),(38,'Hummus','40','g'),(38,'Lechuga','2','hojas'),(38,'Zanahoria rallada','30','g'),
-- 39. Gazpacho
(39,'Tomate maduro','500','g'),(39,'Pimiento verde','1','unidad'),(39,'Pepino','0.5','unidad'),(39,'Ajo','1','diente'),(39,'Aceite de oliva','3','cucharadas'),
-- 40. Bacalao con Pisto
(40,'Bacalao','200','g'),(40,'Calabacín','1','unidad'),(40,'Pimiento rojo','1','unidad'),(40,'Tomate','2','unidades'),(40,'Cebolla','1','unidad'),
-- 41. Huevos al Horno
(41,'Huevos','2','unidades'),(41,'Espinacas','100','g'),(41,'Tomate','1','unidad'),(41,'Queso rallado','20','g'),
-- 42. Ensalada de Pasta
(42,'Pasta corta integral','120','g'),(42,'Atún en conserva','80','g'),(42,'Maíz','50','g'),(42,'Tomate cherry','8','unidades'),(42,'Aceitunas','20','g'),
-- 43. Hummus
(43,'Garbanzos cocidos','200','g'),(43,'Tahini','2','cucharadas'),(43,'Limón','1','unidad'),(43,'Zanahoria','2','unidades'),(43,'Pepino','1','unidad'),
-- 44. Yogur de Soja
(44,'Yogur de soja','150','g'),(44,'Nueces','20','g'),(44,'Semillas de lino','1','cucharada'),(44,'Sirope de agave','1','cucharadita'),
-- 45. Barritas Energéticas
(45,'Copos de avena','120','g'),(45,'Dátiles','80','g'),(45,'Mantequilla de cacahuete','40','g'),(45,'Chocolate negro','30','g'),
-- 46. Tostada de Queso
(46,'Pan integral','1','rebanada'),(46,'Queso fresco','40','g'),(46,'Tomate','0.5','unidad'),(46,'Orégano','1','pizca'),
-- 47. Mix de Frutos Secos
(47,'Almendras','30','g'),(47,'Nueces','30','g'),(47,'Anacardos','20','g'),(47,'Pasas','20','g'),(47,'Arándanos deshidratados','15','g'),
-- 48. Batido Verde
(48,'Espinacas','50','g'),(48,'Manzana verde','1','unidad'),(48,'Jengibre','5','g'),(48,'Limón','0.5','unidad'),(48,'Pepino','0.5','unidad'),
-- 49. Guacamole
(49,'Aguacate','2','unidades'),(49,'Tomate','1','unidad'),(49,'Cebolla','0.25','unidad'),(49,'Lima','1','unidad'),(49,'Nachos integrales','50','g'),
-- 50. Manzana con Cacahuete
(50,'Manzana','1','unidad'),(50,'Mantequilla de cacahuete','2','cucharadas'),(50,'Canela','1','pizca');

-- ============================================
-- SEED STEPS (3-4 per recipe, abbreviated)
-- ============================================

INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES
(1,1,'Tuesta las rebanadas de pan integral hasta que estén doradas y crujientes.'),(1,2,'Corta el aguacate por la mitad, retira el hueso y aplástalo con un tenedor sobre las tostadas.'),(1,3,'Pocha el huevo en agua hirviendo con un chorrito de vinagre durante 3 minutos.'),(1,4,'Coloca el huevo pochado sobre el aguacate y espolvorea con semillas de sésamo, sal y pimienta.'),
(2,1,'Pon la avena con la leche vegetal en un cazo a fuego medio.'),(2,2,'Remueve constantemente durante 8-10 minutos hasta obtener una textura cremosa.'),(2,3,'Sirve en un bol y decora con rodajas de plátano, arándanos y canela.'),
(3,1,'Bate los huevos con sal y finas hierbas hasta que estén espumosos.'),(3,2,'Derrite la mantequilla en una sartén antiadherente a fuego medio-bajo.'),(3,3,'Vierte los huevos y cuando empiecen a cuajar, añade el queso en el centro.'),(3,4,'Dobla la tortilla y sirve cuando el queso esté fundido.'),
(4,1,'Tritura la pulpa de açaí congelada con el plátano hasta obtener una textura espesa.'),(4,2,'Vierte en un bol y alisa la superficie.'),(4,3,'Decora con la granola, rodajas de plátano, coco rallado y semillas de chía.'),
(5,1,'Bate los huevos ligeramente con sal y pimienta.'),(5,2,'Saltea las espinacas en aceite de oliva hasta que se marchiten.'),(5,3,'Añade los huevos y remueve a fuego suave hasta que cuajen cremosamente.'),(5,4,'Sirve con los tomates cherry cortados por la mitad.'),
(6,1,'Tritura la avena y el plátano hasta obtener una masa homogénea.'),(6,2,'Añade el huevo y mezcla bien.'),(6,3,'Cocina porciones en una sartén antiadherente a fuego medio, 2 min por cada lado.'),(6,4,'Sirve con frutos rojos por encima.'),
(7,1,'Sirve el yogur griego en un bol.'),(7,2,'Añade la granola y las frutas de temporada troceadas.'),(7,3,'Riega con un hilo de miel y disfruta.'),
(8,1,'Tuesta el pan integral hasta que esté crujiente.'),(8,2,'Ralla el tomate maduro sobre las tostadas.'),(8,3,'Añade un chorrito de aceite de oliva y coloca el jamón serrano encima.'),
(9,1,'Añade todos los ingredientes en una batidora.'),(9,2,'Tritura durante 30 segundos hasta obtener una textura cremosa y homogénea.'),(9,3,'Sirve frío en un vaso alto.'),
(10,1,'Hierve el agua y añade los copos de avena.'),(10,2,'Cocina a fuego lento durante 8 minutos removiendo de vez en cuando.'),(10,3,'Trocea la manzana y añádela junto con las nueces y la canela.'),
(11,1,'Extiende la tortilla integral y unta con un poco de mayonesa ligera.'),(11,2,'Coloca las lonchas de pavo, el aguacate en rodajas, la lechuga y el tomate.'),(11,3,'Enrolla firmemente y corta por la mitad en diagonal.'),
(12,1,'Lava y trocea las frutas variadas.'),(12,2,'Colócalas en un bol y riega con el zumo de naranja.'),(12,3,'Espolvorea las semillas de chía por encima.'),
(13,1,'Salpimenta la pechuga de pollo y cocínala a la plancha 6-7 min por cada lado.'),(13,2,'Prepara la ensalada con lechugas, tomate cherry cortado y pepino en rodajas.'),(13,3,'Coloca el pollo sobre la ensalada y aliña con aceite de oliva y zumo de limón.'),
(14,1,'Cuece la pasta integral en agua con sal según las instrucciones del paquete.'),(14,2,'Saltea el calabacín, pimiento y champiñones en aceite de oliva durante 8 minutos.'),(14,3,'Mezcla la pasta con las verduras y la salsa de tomate. Cocina 3 minutos más.'),
(15,1,'Precalienta el horno a 200°C. Coloca el salmón en una bandeja con papel de horno.'),(15,2,'Cuece el brócoli al vapor durante 5 minutos y asa las patatas baby 20 minutos.'),(15,3,'Hornea el salmón 15 minutos. Sirve con eneldo fresco y zumo de limón.'),
(16,1,'Cocina la pechuga de pollo a la plancha y córtala en tiras.'),(16,2,'Trocea la lechuga romana y colócala en un bol grande.'),(16,3,'Añade el pollo, los crutones y el parmesano rallado.'),(16,4,'Aliña con la salsa César ligera y mezcla bien.'),
(17,1,'Cuece la quinoa en agua con sal durante 15 minutos.'),(17,2,'Asa los garbanzos con especias en el horno a 200°C durante 20 minutos.'),(17,3,'Monta el bowl con quinoa, garbanzos, aguacate, zanahoria rallada y edamame.'),(17,4,'Aliña con tahini diluido en limón y agua.'),
(18,1,'Cuece el arroz integral según las instrucciones.'),(18,2,'Corta el pollo en dados y saltéalo en un wok con un poco de aceite.'),(18,3,'Añade las verduras y la salsa de soja. Saltea 5 minutos.'),(18,4,'Incorpora el arroz y mezcla todo bien a fuego fuerte.'),
(19,1,'Pon las lentejas en remojo con la zanahoria, patata y laurel.'),(19,2,'Cuece durante 35-40 minutos a fuego medio hasta que estén tiernas.'),(19,3,'Añade el pimentón, remueve y deja reposar 5 minutos antes de servir.'),
(20,1,'Salpimenta la pechuga de pavo y cocínala a la plancha.'),(20,2,'Corta las patatas en gajos y ásalas con romero en el horno a 200°C durante 25 min.'),(20,3,'Sirve con ensalada verde aliñada.'),
(21,1,'Escurre y enjuaga los garbanzos.'),(21,2,'Corta el pepino, tomate y cebolla morada en dados.'),(21,3,'Mezcla todo en un bol con las aceitunas y aliña con vinagreta.'),
(22,1,'Corta el tofu en cubos y márcalo en el wok con aceite hasta dorarlo.'),(22,2,'Añade las verduras cortadas y saltea a fuego fuerte 5 minutos.'),(22,3,'Incorpora la salsa teriyaki y cocina 2 minutos más removiendo.'),
(23,1,'Salpimenta los filetes de merluza.'),(23,2,'Cocínalos a la plancha con aceite de oliva, 4 minutos por cada lado.'),(23,3,'Sirve sobre pimientos asados y aliña con limón.'),
(24,1,'Calienta el caldo de verduras y mantenlo caliente.'),(24,2,'Sofríe los champiñones y añade el arroz. Remueve 2 minutos.'),(24,3,'Añade el caldo poco a poco, removiendo constantemente durante 20 minutos.'),(24,4,'Termina con parmesano y el aceite de trufa.'),
(25,1,'Corta el pollo en tiras y salpimenta con las especias mexicanas.'),(25,2,'Saltea el pollo a fuego fuerte. Reserva.'),(25,3,'Saltea los pimientos y la cebolla en el mismo sartén.'),(25,4,'Sirve en las tortillas con el pollo y las verduras.'),
(26,1,'Corta la calabaza y ásala en el horno a 200°C durante 25 minutos.'),(26,2,'Tritura la calabaza asada con el jengibre y la leche de coco.'),(26,3,'Sirve caliente decorada con semillas de calabaza tostadas.'),
(27,1,'Cuece el arroz de sushi y aliñalo con vinagre de arroz.'),(27,2,'Corta el salmón en cubos y el aguacate en láminas.'),(27,3,'Monta el bowl con arroz, salmón, aguacate, edamame y mango.'),(27,4,'Aliña con salsa ponzu.'),
(28,1,'Pela y corta todas las verduras en trozos regulares.'),(28,2,'Sofríe el puerro y el apio. Añade el resto de verduras y el caldo.'),(28,3,'Cuece a fuego medio durante 25 minutos.'),(28,4,'Tritura parcialmente si deseas una textura más cremosa.'),
(29,1,'Pela y corta las patatas en láminas finas. Confítalas en aceite a fuego suave.'),(29,2,'Pocha la cebolla cortada fina hasta que esté transparente.'),(29,3,'Bate los huevos con sal y mezcla con las patatas y la cebolla.'),(29,4,'Cuaja la tortilla en sartén, dándole la vuelta con un plato.'),
(30,1,'Limpia y trocea las setas y los espárragos.'),(30,2,'Saltéalos en una sartén con aceite de oliva a fuego fuerte.'),(30,3,'Bate los huevos, viértelos sobre las verduras y remueve a fuego suave.'),
(31,1,'Marina la pechuga con zumo de limón, hierbas y aceite durante 15 minutos.'),(31,2,'Cocínala a la plancha 6-7 minutos por cada lado.'),(31,3,'Sirve con las verduras al vapor.'),
(32,1,'Cuece la quinoa y déjala enfriar ligeramente.'),(32,2,'Mezcla con espinacas frescas, tomate seco troceado y aceitunas.'),(32,3,'Desmenuza el queso feta por encima y aliña con aceite de oliva.'),
(33,1,'Precalienta el horno a 190°C.'),(33,2,'Coloca las patatas en rodajas con cebolla y pimiento en una bandeja.'),(33,3,'Pon la lubina encima, riega con aceite y hornea 30 minutos.'),
(34,1,'Tritura las lentejas con la avena y la cebolla picada.'),(34,2,'Forma las hamburguesas y espolvorea con especias.'),(34,3,'Cocínalas a la plancha 5 minutos por cada lado.'),
(35,1,'Cuece el brócoli con la patata y el puerro en agua durante 15 minutos.'),(35,2,'Tritura todo hasta obtener una crema suave.'),(35,3,'Añade nuez moscada y sirve caliente.'),
(36,1,'Limpia las sardinas y sazónalas con sal.'),(36,2,'Cocínalas a la plancha 3-4 minutos por cada lado.'),(36,3,'Prepara la ensalada y sirve junto a las sardinas con un chorrito de limón.'),
(37,1,'Corta los calabacines por la mitad y vacía el interior.'),(37,2,'Cuece la quinoa y mezcla con las verduras salteadas.'),(37,3,'Rellena los calabacines, cubre con salsa de tomate y hornea 25 min a 190°C.'),
(38,1,'Calienta la tortilla integral brevemente.'),(38,2,'Unta con hummus y añade el pollo desmenuzado.'),(38,3,'Añade la lechuga, tomate y zanahoria rallada. Enrolla firmemente.'),
(39,1,'Trocea el tomate, pimiento, pepino y ajo.'),(39,2,'Tritura todo con aceite de oliva hasta obtener una textura fina y homogénea.'),(39,3,'Refrigera al menos 2 horas antes de servir bien frío.'),
(40,1,'Prepara el pisto pochando la cebolla, pimiento, calabacín y tomate.'),(40,2,'Salpimenta el bacalao y hornéalo 15 minutos a 200°C.'),(40,3,'Sirve el bacalao sobre una cama de pisto.'),
(41,1,'Saltea las espinacas en una sartén apta para horno.'),(41,2,'Añade el tomate troceado y haz dos huecos para los huevos.'),(41,3,'Espolvorea queso rallado y hornea a 200°C durante 12 minutos.'),
(42,1,'Cuece la pasta integral y déjala enfriar.'),(42,2,'Mezcla con el atún escurrido, maíz, tomates cherry y aceitunas.'),(42,3,'Aliña con vinagreta de mostaza y sirve fría.'),
(43,1,'Tritura los garbanzos con tahini, zumo de limón, ajo y un poco de agua.'),(43,2,'Corta la zanahoria y el pepino en bastones.'),(43,3,'Sirve el hummus en un plato con las crudités alrededor.'),
(44,1,'Sirve el yogur de soja en un bol.'),(44,2,'Trocea las nueces y espárcelas por encima.'),(44,3,'Añade las semillas de lino y un toque de sirope de agave.'),
(45,1,'Tritura los dátiles con la mantequilla de cacahuete hasta formar una pasta.'),(45,2,'Mezcla con la avena y el chocolate negro troceado.'),(45,3,'Forma barritas, colócalas en una bandeja y refrigera 2 horas.'),
(46,1,'Tuesta el pan integral.'),(46,2,'Coloca el queso fresco y las rodajas de tomate encima.'),(46,3,'Espolvorea orégano y un chorrito de aceite de oliva.'),
(47,1,'Mezcla todos los frutos secos y los frutos deshidratados en un bol.'),(47,2,'Reparte en porciones individuales para llevar.'),
(48,1,'Añade las espinacas, manzana, jengibre, pepino y limón a la batidora.'),(48,2,'Tritura con un poco de agua hasta obtener un batido homogéneo.'),(48,3,'Sirve inmediatamente para conservar todos los nutrientes.'),
(49,1,'Corta los aguacates por la mitad y extrae la pulpa.'),(49,2,'Aplasta con un tenedor y mezcla con tomate picado, cebolla, cilantro y lima.'),(49,3,'Sirve con nachos integrales.'),
(50,1,'Lava la manzana y córtala en rodajas.'),(50,2,'Unta cada rodaja con mantequilla de cacahuete.'),(50,3,'Espolvorea con canela al gusto.');

-- ============================================
-- SEED TAGS
-- ============================================

INSERT INTO recipe_tags (recipe_id, tag) VALUES
(1,'alto-proteina'),(1,'rapido'),
(2,'sin-gluten'),(2,'sin-lactosa'),(2,'bajo-calorias'),
(3,'rapido'),(3,'alto-proteina'),(3,'contiene-lactosa'),
(4,'sin-gluten'),(4,'sin-lactosa'),(4,'antioxidante'),
(5,'alto-proteina'),(5,'bajo-calorias'),(5,'sin-gluten'),
(6,'sin-lactosa'),
(7,'alto-proteina'),(7,'contiene-lactosa'),
(8,'rapido'),
(9,'alto-proteina'),(9,'sin-lactosa'),(9,'contiene-lactosa'),
(10,'sin-lactosa'),(10,'contiene-frutos-secos'),
(11,'alto-proteina'),(11,'rapido'),
(12,'sin-gluten'),(12,'sin-lactosa'),(12,'bajo-calorias'),
(13,'alto-proteina'),(13,'sin-gluten'),(13,'bajo-calorias'),
(14,'sin-lactosa'),
(15,'alto-proteina'),(15,'contiene-gluten'),
(16,'alto-proteina'),
(17,'sin-gluten'),(17,'alto-proteina'),(17,'sin-lactosa'),
(18,'sin-lactosa'),
(19,'sin-gluten'),(19,'sin-lactosa'),(19,'alto-proteina'),
(20,'alto-proteina'),(20,'sin-gluten'),
(21,'sin-gluten'),(21,'sin-lactosa'),(21,'alto-proteina'),
(22,'sin-gluten'),(22,'sin-lactosa'),(22,'alto-proteina'),
(23,'alto-proteina'),(23,'sin-gluten'),(23,'bajo-calorias'),
(24,'contiene-lactosa'),
(25,'alto-proteina'),
(26,'sin-gluten'),(26,'sin-lactosa'),(26,'bajo-calorias'),
(27,'alto-proteina'),(27,'contiene-mariscos'),
(28,'sin-gluten'),(28,'sin-lactosa'),(28,'bajo-calorias'),
(29,'contiene-gluten'),
(30,'sin-gluten'),(30,'bajo-calorias'),
(31,'alto-proteina'),(31,'sin-gluten'),(31,'bajo-calorias'),
(32,'sin-gluten'),(32,'contiene-lactosa'),
(33,'alto-proteina'),(33,'sin-gluten'),
(34,'sin-lactosa'),(34,'alto-proteina'),
(35,'sin-gluten'),(35,'sin-lactosa'),(35,'bajo-calorias'),
(36,'alto-proteina'),(36,'sin-gluten'),(36,'contiene-mariscos'),
(37,'sin-gluten'),(37,'sin-lactosa'),(37,'bajo-calorias'),
(38,'alto-proteina'),
(39,'sin-gluten'),(39,'sin-lactosa'),(39,'bajo-calorias'),
(40,'alto-proteina'),(40,'sin-gluten'),
(41,'alto-proteina'),(41,'sin-gluten'),(41,'contiene-lactosa'),
(42,'contiene-gluten'),
(43,'sin-gluten'),(43,'sin-lactosa'),(43,'bajo-calorias'),
(44,'sin-gluten'),(44,'sin-lactosa'),(44,'contiene-frutos-secos'),
(45,'contiene-frutos-secos'),
(46,'contiene-lactosa'),(46,'rapido'),
(47,'sin-gluten'),(47,'sin-lactosa'),(47,'contiene-frutos-secos'),
(48,'sin-gluten'),(48,'sin-lactosa'),(48,'bajo-calorias'),
(49,'sin-gluten'),(49,'sin-lactosa'),
(50,'sin-gluten'),(50,'sin-lactosa'),(50,'contiene-frutos-secos');
