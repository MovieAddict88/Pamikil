-- ============================================================
-- CHILDREN'S LEARNING PLATFORM - SAMPLE DATA
-- ============================================================
-- Version: 1.0
-- Description: Sample data for immediate testing and demonstration
-- Run this after schema.sql to populate the database
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- SUBJECTS
-- ============================================================
INSERT INTO `subjects` (`name`, `slug`, `description`, `icon`, `color`, `display_order`) VALUES
('Math', 'math', 'Learn numbers, counting, addition, subtraction, and more!', 'fa-calculator', '#3498db', 1),
('Science', 'science', 'Explore the wonders of nature, animals, and space!', 'fa-flask', '#2ecc71', 2),
('Language Arts', 'language', 'Discover letters, words, reading, and writing!', 'fa-book', '#e74c3c', 3),
('Social Studies', 'social', 'Learn about communities, countries, and cultures!', 'fa-globe', '#f39c12', 4),
('Art', 'art', 'Express creativity through colors, shapes, and designs!', 'fa-palette', '#9b59b6', 5);

-- ============================================================
-- AGE GROUPS
-- ============================================================
INSERT INTO `age_groups` (`name`, `min_age`, `max_age`, `description`, `display_order`) VALUES
('Preschool (3-5)', 3, 5, 'Fun activities for little learners', 1),
('Early Elementary (6-8)', 6, 8, 'Foundational skills for young students', 2),
('Upper Elementary (9-12)', 9, 12, 'Advanced activities for older kids', 3);

-- ============================================================
-- DIFFICULTY LEVELS
-- ============================================================
INSERT INTO `difficulty_levels` (`name`, `level_number`, `description`, `xp_reward`, `coin_reward`, `min_level_required`, `display_order`) VALUES
('Beginner', 1, 'Perfect for getting started!', 5, 3, 1, 1),
('Easy', 2, 'A gentle challenge', 10, 5, 1, 2),
('Medium', 3, 'Getting interesting!', 15, 8, 2, 3),
('Hard', 4, 'A real challenge!', 25, 12, 3, 4),
('Expert', 5, 'For the bravest learners!', 40, 20, 5, 5);

-- ============================================================
-- ADMIN USER
-- ============================================================
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `is_active`, `coins`, `total_xp`, `current_level`) VALUES
('admin', 'admin@pamikil.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User', 1, 1000, 5000, 10);
-- Password: password

-- ============================================================
-- SAMPLE PARENT ACCOUNT
-- ============================================================
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `is_active`, `coins`, `total_xp`, `current_level`) VALUES
('parent1', 'parent@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Sarah', 'Johnson', 1, 0, 0, 1);
-- Password: password

-- ============================================================
-- SAMPLE STUDENT ACCOUNTS
-- ============================================================
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `parent_id`, `date_of_birth`, `is_active`, `coins`, `total_xp`, `current_level`) VALUES
('student1', 'kid1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Emma', 'Johnson', (SELECT id FROM users WHERE username='parent1'), '2018-05-15', 1, 150, 300, 3),
('student2', 'kid2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Jake', 'Johnson', (SELECT id FROM users WHERE username='parent1'), '2016-08-22', 1, 350, 800, 5);
-- Password: password

-- ============================================================
-- ACTIVITY TAGS
-- ============================================================
INSERT INTO `activity_tags` (`tag_name`, `slug`, `description`) VALUES
('Numbers', 'numbers', 'Activities focusing on counting and numbers'),
('Colors', 'colors', 'Learn about different colors'),
('Animals', 'animals', 'Explore the animal kingdom'),
('Shapes', 'shapes', 'Identify and learn about shapes'),
('Letters', 'letters', 'Learn the alphabet and letters'),
('Addition', 'addition', 'Basic addition problems'),
('Phonics', 'phonics', 'Phonics and letter sounds'),
('Space', 'space', 'Explore outer space'),
('Weather', 'weather', 'Learn about weather and seasons'),
('Community', 'community', 'Community helpers and places'),
('Counting', 'counting', 'Count from 1 to 10 and beyond'),
('Reading', 'reading', 'Reading comprehension activities'),
('Patterns', 'patterns', 'Find and complete patterns'),
('Measurement', 'measurement', 'Learn about measuring'),
('Nature', 'nature', 'Explore plants and nature');

-- ============================================================
-- QUIZ ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='math'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'quiz', 'Count the Apples', 'count-apples', 'Learn to count from 1 to 5 by counting apples!', 'Count the apples in each picture and select the correct number.', 
'{"questions": [{"id": 1, "question": "How many apples do you see?", "image": "🍎🍎", "options": [1, 2, 3, 4], "correct": 2, "explanation": "Count carefully! There are 2 red apples.", "feedback_audio": "Great job counting!"}, {"id": 2, "question": "How many apples now?", "image": "🍎🍎🍎", "options": [2, 3, 4, 5], "correct": 3, "explanation": "You counted 3 apples! Well done!", "feedback_audio": "Perfect!"}, {"id": 3, "question": "Count these apples!", "image": "🍎🍎🍎🍎", "options": [3, 4, 5, 6], "correct": 4, "explanation": "Four apples! You\'re doing great!", "feedback_audio": "Excellent!"}, {"id": 4, "question": "How many apples are there?", "image": "🍎", "options": [0, 1, 2, 3], "correct": 1, "explanation": "Just one apple! Keep going!", "feedback_audio": "Correct!"}, {"id": 5, "question": "Final counting challenge!", "image": "🍎🍎🍎🍎🍎", "options": [4, 5, 6, 7], "correct": 5, "explanation": "You found all 5 apples! Amazing!", "feedback_audio": "You\'re a counting star!"}]}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='language'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'quiz', 'Learn Your Colors', 'learn-colors', 'Match colors with their names!', 'Look at each color and choose the correct name.',
'{"questions": [{"id": 1, "question": "What color is this?", "color": "#FF0000", "options": ["Blue", "Red", "Green", "Yellow"], "correct": 1, "explanation": "Red is the color of apples and fire trucks!", "feedback_audio": "Red!"}, {"id": 2, "question": "What color is this?", "color": "#0000FF", "options": ["Blue", "Red", "Green", "Yellow"], "correct": 0, "explanation": "Blue is the color of the sky and ocean!", "feedback_audio": "Blue!"}, {"id": 3, "question": "What color is this?", "color": "#00FF00", "options": ["Blue", "Red", "Green", "Yellow"], "correct": 2, "explanation": "Green is the color of grass and leaves!", "feedback_audio": "Green!"}, {"id": 4, "question": "What color is this?", "color": "#FFFF00", "options": ["Blue", "Red", "Green", "Yellow"], "correct": 3, "explanation": "Yellow is the color of the sun and bananas!", "feedback_audio": "Yellow!"}]}',
NULL, 75, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='science'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'quiz', 'Animal Sounds Quiz', 'animal-sounds', 'Guess which animal makes each sound!', 'Listen carefully and match each sound to the right animal.',
'{"questions": [{"id": 1, "question": "Which animal says 'Moo'?", "options": ["Cat", "Dog", "Cow", "Horse"], "correct": 2, "explanation": "Cows say 'Moo!' on the farm.", "feedback_audio": "Moo! Correct!"}, {"id": 2, "question": "Which animal says 'Meow'?", "options": ["Cat", "Dog", "Cow", "Bird"], "correct": 0, "explanation": "Cats say 'Meow!' when they want attention.", "feedback_audio": "Meow! Great job!"}, {"id": 3, "question": "Which animal says 'Woof'?", "options": ["Cat", "Dog", "Sheep", "Pig"], "correct": 1, "explanation": "Dogs say 'Woof!' to communicate.", "feedback_audio": "Woof! Correct!"}, {"id": 4, "question": "Which animal says 'Oink'?", "options": ["Cow", "Sheep", "Pig", "Chicken"], "correct": 2, "explanation": "Pigs say 'Oink!' on the farm.", "feedback_audio": "Oink! Good work!"}, {"id": 5, "question": "Which animal says 'Baa'?", "options": ["Horse", "Sheep", "Goat", "Duck"], "correct": 1, "explanation": "Sheep say 'Baa!' in the meadow.", "feedback_audio": "Baa! Perfect!"}]}',
NULL, 60, 10, 5, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='math'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'quiz', 'Simple Addition', 'simple-addition', 'Practice adding numbers together!', 'Solve each addition problem and choose the right answer.',
'{"questions": [{"id": 1, "question": "What is 2 + 3?", "options": [4, 5, 6, 7], "correct": 1, "explanation": "2 + 3 = 5. Start with 2 and count up 3 more: 3, 4, 5!", "feedback_audio": "Five! Correct!"}, {"id": 2, "question": "What is 4 + 1?", "options": [4, 5, 6, 3], "correct": 1, "explanation": "4 + 1 = 5. Add one more to 4!", "feedback_audio": "Five! Great!"}, {"id": 3, "question": "What is 3 + 3?", "options": [5, 6, 7, 8], "correct": 1, "explanation": "3 + 3 = 6. Double of 3 is 6!", "feedback_audio": "Six! Perfect!"}, {"id": 4, "question": "What is 5 + 2?", "options": [6, 7, 8, 9], "correct": 1, "explanation": "5 + 2 = 7. Count up from 5 two times!", "feedback_audio": "Seven! Awesome!"}, {"id": 5, "question": "What is 1 + 4?", "options": [4, 5, 6, 7], "correct": 1, "explanation": "1 + 4 = 5. You can flip it: 4 + 1 is the same!", "feedback_audio": "Five! Excellent!"}]}',
NULL, 60, 10, 5, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- STORY ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='language'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'story', 'The Lost Puppy', 'lost-puppy', 'Read a heartwarming story about a lost puppy!', 'Read the story and answer questions about what happened.',
'{"story": "Once upon a time, there was a little puppy named Max. Max was a happy, playful golden retriever with soft, fluffy fur. One sunny morning, Max saw a beautiful butterfly in the garden. Being a curious puppy, he chased the butterfly far away from home. He ran through the meadow, past the old oak tree, and over the little bridge. When Max finally stopped, he looked around but didn\'t recognize anything. He was lost! Max felt scared and alone. He sat down and let out a sad \"Woof.\" Suddenly, he heard footsteps. It was Emma, a kind girl who lived nearby. She saw Max\'s name on his collar and called his family. Max was so happy to go home! He promised never to chase butterflies too far again.", "questions": [{"id": 1, "question": "What kind of dog was Max?", "options": ["Poodle", "Golden Retriever", "Beagle", "Bulldog"], "correct": 1, "explanation": "The story says Max was a golden retriever with soft, fluffy fur."}, {"id": 2, "question": "What was Max chasing?", "options": ["A bird", "A ball", "A butterfly", "A cat"], "correct": 2, "explanation": "Max saw a beautiful butterfly and started chasing it."}, {"id": 3, "question": "How did Max feel when he was lost?", "options": ["Happy", "Excited", "Scared", "Angry"], "correct": 2, "explanation": "The story says Max felt scared and alone when he couldn\'t find his way home."}, {"id": 4, "question": "Who found Max?", "options": ["A police officer", "Emma", "Max\'s owner", "A mail carrier"], "correct": 1, "explanation": "Emma, a kind girl who lived nearby, found Max and helped him."}, {"id": 5, "question": "What did Max have on that helped identify him?", "options": ["A collar", "A sweater", "A hat", "A backpack"], "correct": 0, "explanation": "Emma saw Max\'s name on his collar, which helped identify him."}]}',
NULL, 60, 15, 8, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='science'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'story', 'The Hungry Caterpillar', 'hungry-caterpillar', 'Learn about the life cycle of a butterfly!', 'Follow the caterpillar\'s journey and answer questions.',
'{"story": "One bright morning, a tiny caterpillar hatched from an egg on a green leaf. The caterpillar was very hungry! He started eating the leaf, munch, munch, munch. The more he ate, the bigger he grew. He ate leaves all day long. One day, the caterpillar felt very tired and full. He spun himself a cozy cocoon and went to sleep inside. For many days, the caterpillar rested in his cocoon. Then, something amazing happened! The cocoon opened, and out came a beautiful butterfly with colorful wings! The butterfly flew high into the sky, visiting flowers and enjoying the sunshine.", "questions": [{"id": 1, "question": "What came out of the egg?", "options": ["A butterfly", "A caterpillar", "A moth", "A bee"], "correct": 1, "explanation": "A tiny caterpillar hatched from the egg."}, {"id": 2, "question": "What did the caterpillar do all day?", "options": ["Fly", "Sleep", "Eat leaves", "Play"], "correct": 2, "explanation": "The caterpillar ate leaves all day long."}, {"id": 3, "question": "Where did the caterpillar go to sleep?", "options": ["On a flower", "In a cocoon", "Under a rock", "In a tree"], "correct": 1, "explanation": "The caterpillar spun a cocoon and went to sleep inside."}, {"id": 4, "question": "What did the caterpillar become?", "options": ["A moth", "A butterfly", "A dragonfly", "A bee"], "correct": 1, "explanation": "The caterpillar turned into a beautiful butterfly!"}]}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- DRAG AND DROP ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='language'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'dragdrop', 'Match the Letters', 'match-letters', 'Match uppercase letters to lowercase letters!', 'Drag each lowercase letter to its matching uppercase letter.',
'{"items": [{"id": 1, "match": "A", "draggable": "a", "image": null}, {"id": 2, "match": "B", "draggable": "b", "image": null}, {"id": 3, "match": "C", "draggable": "c", "image": null}, {"id": 4, "match": "D", "draggable": "d", "image": null}, {"id": 5, "match": "E", "draggable": "e", "image": null}], "drop_zones": [{"id": 1, "label": "A"}, {"id": 2, "label": "B"}, {"id": 3, "label": "C"}, {"id": 4, "label": "D"}, {"id": 5, "label": "E"}], "success_message": "Great job matching letters!", "feedback": {"correct": "Correct match!", "incorrect": "Try again!"}}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='science'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'dragdrop', 'Animal Homes', 'animal-homes', 'Match animals to their homes!', 'Drag each animal to the correct home.',
'{"items": [{"id": 1, "match": "farm", "draggable": "🐄", "image": null, "name": "Cow"}, {"id": 2, "match": "ocean", "draggable": "🐟", "image": null, "name": "Fish"}, {"id": 3, "match": "tree", "draggable": "🐦", "image": null, "name": "Bird"}, {"id": 4, "match": "web", "draggable": "🕷️", "image": null, "name": "Spider"}, {"id": 5, "match": "hive", "draggable": "🐝", "image": null, "name": "Bee"}], "drop_zones": [{"id": 1, "label": "🌾 Farm", "match": "farm"}, {"id": 2, "label": "🌊 Ocean", "match": "ocean"}, {"id": 3, "label": "🌳 Tree", "match": "tree"}, {"id": 4, "label": "🕸️ Web", "match": "web"}, {"id": 5, "label": "🏠 Hive", "match": "hive"}], "success_message": "You know where animals live!", "feedback": {"correct": "That\'s right!", "incorrect": "Try thinking about where this animal lives!"}}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='math'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'dragdrop', 'Sort by Size', 'sort-by-size', 'Sort objects from smallest to largest!', 'Drag the objects into the correct order from smallest to largest.',
'{"items": [{"id": 1, "size": 1, "label": "🐜 Ant", "draggable": true}, {"id": 2, "size": 2, "label": "🐁 Mouse", "draggable": true}, {"id": 3, "size": 3, "label": "🐱 Cat", "draggable": true}, {"id": 4, "size": 4, "label": "🐶 Dog", "draggable": true}, {"id": 5, "size": 5, "label": "🐘 Elephant", "draggable": true}], "drop_zones": [{"id": 1, "position": 1, "label": "Smallest"}, {"id": 2, "position": 2, "label": ""}, {"id": 3, "position": 3, "label": ""}, {"id": 4, "position": 4, "label": ""}, {"id": 5, "position": 5, "label": "Largest"}], "success_message": "Perfect sorting!", "feedback": {"correct": "Good job!", "incorrect": "Check the sizes again!"}}',
NULL, 60, 10, 5, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- FLASHCARD ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='math'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'flashcard', 'Number Flashcards', 'number-flashcards', 'Learn numbers 1-10 with colorful flashcards!', 'Click on each card to flip and see the number!',
'{"cards": [{"id": 1, "front": "❓", "back": "1️⃣ One", "audio": "one.mp3"}, {"id": 2, "front": "❓", "back": "2️⃣ Two", "audio": "two.mp3"}, {"id": 3, "front": "❓", "back": "3️⃣ Three", "audio": "three.mp3"}, {"id": 4, "front": "❓", "back": "4️⃣ Four", "audio": "four.mp3"}, {"id": 5, "front": "❓", "back": "5️⃣ Five", "audio": "five.mp3"}, {"id": 6, "front": "❓", "back": "6️⃣ Six", "audio": "six.mp3"}, {"id": 7, "front": "❓", "back": "7️⃣ Seven", "audio": "seven.mp3"}, {"id": 8, "front": "❓", "back": "8️⃣ Eight", "audio": "eight.mp3"}, {"id": 9, "front": "❓", "back": "9️⃣ Nine", "audio": "nine.mp3"}, {"id": 10, "front": "❓", "back": "🔟 Ten", "audio": "ten.mp3"}], "shuffle": true, "auto_play_audio": false}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='language'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'flashcard', 'ABC Flashcards', 'abc-flashcards', 'Learn the alphabet with fun flashcards!', 'Click on each card to flip and see the letter!',
'{"cards": [{"id": 1, "front": "🍎", "back": "A is for Apple"}, {"id": 2, "front": "🦋", "back": "B is for Butterfly"}, {"id": 3, "front": "🐱", "back": "C is for Cat"}, {"id": 4, "front": "🐕", "back": "D is for Dog"}, {"id": 5, "front": "🐘", "back": "E is for Elephant"}, {"id": 6, "front": "🐸", "back": "F is for Frog"}, {"id": 7, "front": "🍇", "back": "G is for Grapes"}, {"id": 8, "front": "🏠", "back": "H is for House"}], "shuffle": true, "auto_play_audio": false}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='science'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'flashcard', 'Solar System Flashcards', 'solar-system-flashcards', 'Learn about planets in our solar system!', 'Click on each card to flip and learn about each planet!',
'{"cards": [{"id": 1, "front": "☀️ The Sun", "back": "The Sun is a star at the center of our solar system. It gives us light and heat!"}, {"id": 2, "front": "☿ Mercury", "back": "Mercury is the smallest planet and closest to the Sun. It has no moons!"}, {"id": 3, "front": "♀️ Venus", "back": "Venus is the hottest planet! It\'s called Earth\'s twin because they\'re similar in size."}, {"id": 4, "front": "🌍 Earth", "back": "Earth is our home! It\'s the only planet known to have life and liquid water."}, {"id": 5, "front": "♂️ Mars", "back": "Mars is called the Red Planet because of its red color. It has two tiny moons."}, {"id": 6, "front": "♃ Jupiter", "back": "Jupiter is the largest planet! It has a famous storm called the Great Red Spot."}, {"id": 7, "front": "♄ Saturn", "back": "Saturn is famous for its beautiful rings made of ice and rock."}, {"id": 8, "front": "♅ Uranus", "back": "Uranus rotates on its side! It\'s an ice giant with a blue-green color."}], "shuffle": true, "auto_play_audio": false}',
NULL, 60, 10, 5, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- PUZZLE ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='art'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'puzzle', 'Simple Shapes Puzzle', 'simple-shapes-puzzle', 'Put the colorful shapes together!', 'Drag the pieces to complete the puzzle.',
'{"image": "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2ZmZmZmZiIvPjxjaXJjbGUgY3g9IjE1MCIgY3k9IjE1MCIgcj0iMTAwIiBmaWxsPSIjZmY2YjZiIi8+PC9zdmc+", "difficulty": "easy", "grid_size": 3, "preview": true}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='art'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'puzzle', 'Flower Puzzle', 'flower-puzzle', 'Solve a beautiful flower puzzle!', 'Drag the pieces to complete the flower picture.',
'{"image": "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iIzkwZWU5MCIvPjxjaXJjbGUgY3g9IjE1MCIgY3k9IjE1MCIgcj0iNjAiIGZpbGw9IiNmZmQ3MDAiLz48Y2lyY2xlIGN4PSIxNTAiIGN5PSI5MCIgcj0iMzAiIGZpbGw9IiNmZmE1MDAiLz48Y2lyY2xlIGN4PSIyMTAiIGN5PSIxNTAiIHI9IjMwIiBmaWxsPSIjZmZhNTAwIi8+PGNpcmNsZSBjeD0iMTUwIiBjeT0iMjEwIiByPSIzMCIgZmlsbD0iI2ZmYTUwMCIvPjxjaXJjbGUgY3g9IjkwIiBjeT0iMTUwIiByPSIzMCIgZmlsbD0iI2ZmYTUwMCIvPjwvc3ZnPg==", "difficulty": "medium", "grid_size": 4, "preview": true}',
NULL, 60, 10, 5, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- CROSSWORD ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='language'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'crossword', 'Animal Crossword', 'animal-crossword', 'Test your animal vocabulary!', 'Read the clues and fill in the crossword puzzle.',
'{"grid": ["C","A","T",".","D","O","G",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".","",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".",".","."], "clues": {"across": [{"number": 1, "row": 0, "col": 0, "length": 3, "clue": "A small pet that says meow", "answer": "CAT", "hint": "It likes to chase mice"}, {"number": 4, "row": 0, "col": 4, "length": 3, "clue": "Man\'s best friend", "answer": "DOG", "hint": "It likes to play fetch"}], "down": [{"number": 1, "row": 0, "col": 0, "length": 3, "clue": "A small pet that says meow", "answer": "CAT", "hint": "It likes to chase mice"}]}, "max_hints": 3}',
NULL, 60, 15, 8, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- MATCHING ACTIVITIES
-- ============================================================
INSERT INTO `activities` (`subject_id`, `age_group_id`, `difficulty_id`, `activity_type`, `title`, `slug`, `description`, `instructions`, `content_data`, `time_limit`, `passing_score`, `xp_reward`, `coin_reward`, `attempts_allowed`, `min_level_required`, `created_by`) VALUES
((SELECT id FROM subjects WHERE slug='language'), (SELECT id FROM age_groups WHERE min_age=3), (SELECT id FROM difficulty_levels WHERE level_number=1), 'matching', 'Word-Image Match', 'word-image-match', 'Match words with their pictures!', 'Click on a word, then click on the matching picture.',
'{"pairs": [{"word": "Apple", "image": "🍎", "audio": "apple.mp3"}, {"word": "Sun", "image": "☀️", "audio": "sun.mp3"}, {"word": "Star", "image": "⭐", "audio": "star.mp3"}, {"word": "Heart", "image": "❤️", "audio": "heart.mp3"}, {"word": "Moon", "image": "🌙", "audio": "moon.mp3"}, {"word": "Cloud", "image": "☁️", "audio": "cloud.mp3"}], "time_limit": 60}',
NULL, 60, 5, 3, NULL, 1, (SELECT id FROM users WHERE username='admin')),

((SELECT id FROM subjects WHERE slug='math'), (SELECT id FROM age_groups WHERE min_age=6), (SELECT id FROM difficulty_levels WHERE level_number=2), 'matching', 'Math Match', 'math-match', 'Match equations with their answers!', 'Click on an equation, then click on its correct answer.',
'{"pairs": [{"equation": "2 + 3", "answer": "5"}, {"equation": "4 + 1", "answer": "5"}, {"equation": "3 + 3", "answer": "6"}, {"equation": "5 + 2", "answer": "7"}, {"equation": "1 + 8", "answer": "9"}, {"equation": "4 + 4", "answer": "8"}], "time_limit": 60}',
NULL, 60, 10, 5, NULL, 1, (SELECT id FROM users WHERE username='admin'));

-- ============================================================
-- BADGES
-- ============================================================
INSERT INTO `badges` (`name`, `slug`, `description`, `badge_type`, `requirement_data`, `coin_reward`, `xp_reward`) VALUES
('First Steps', 'first-steps', 'Complete your first activity!', 'completion', '{"activities_completed": 1}', 10, 5),
('Explorer', 'explorer', 'Complete 10 activities', 'completion', '{"activities_completed": 10}', 50, 25),
('Champion', 'champion', 'Complete 50 activities', 'completion', '{"activities_completed": 50}', 200, 100),
('Master', 'master', 'Complete 100 activities', 'completion', '{"activities_completed": 100}', 500, 250),
('Streak Week', 'streak-week', 'Use the platform for 7 consecutive days', 'streak', '{"consecutive_days": 7}', 100, 50),
('Streak Month', 'streak-month', 'Use the platform for 30 consecutive days', 'streak', '{"consecutive_days": 30}', 500, 250),
('Perfect Score', 'perfect-score', 'Get 100% on any activity', 'score', '{"score_percentage": 100}', 25, 15),
('Speed Demon', 'speed-demon', 'Complete an activity in under 1 minute', 'time', '{"max_seconds": 60}', 30, 20),
('Math Wizard', 'math-wizard', 'Complete 10 math activities', 'special', '{"subject": "math", "activities_completed": 10}', 75, 40),
('Science Star', 'science-star', 'Complete 10 science activities', 'special', '{"subject": "science", "activities_completed": 10}', 75, 40),
('Bookworm', 'bookworm', 'Complete 5 story activities', 'special', '{"activity_type": "story", "activities_completed": 5}', 50, 25),
('Puzzle Master', 'puzzle-master', 'Complete 5 puzzle activities', 'special', '{"activity_type": "puzzle", "activities_completed": 5}', 50, 25),
('Quiz Master', 'quiz-master', 'Complete 10 quiz activities', 'special', '{"activity_type": "quiz", "activities_completed": 10}', 100, 50),
('Collector', 'collector', 'Earn 10 different badges', 'special', '{"badges_earned": 10}', 200, 100),
('Legend', 'legend', 'Reach level 10', 'special', '{"level_reached": 10}', 1000, 500);

-- ============================================================
-- AVATAR ITEMS
-- ============================================================
INSERT INTO `avatar_items` (`name`, `item_type`, `image`, `cost`, `min_level_required`, `is_premium`) VALUES
-- Hair
('Short Brown Hair', 'hair', 'avatar/hair/short_brown.png', 0, 1, 0),
('Long Blonde Hair', 'hair', 'avatar/hair/long_blonde.png', 20, 1, 0),
('Curly Red Hair', 'hair', 'avatar/hair/curly_red.png', 30, 2, 0),
('Black Spiky Hair', 'hair', 'avatar/hair/black_spiky.png', 40, 3, 0),
('Rainbow Hair', 'hair', 'avatar/hair/rainbow.png', 100, 5, 1),

-- Face
('Happy Smile', 'face', 'avatar/face/happy.png', 0, 1, 0),
('Cool Sunglasses', 'face', 'avatar/face/sunglasses.png', 15, 1, 0),
('Cute Glasses', 'face', 'avatar/face/glasses.png', 25, 2, 0),
('Funny Mustache', 'face', 'avatar/face/mustache.png', 20, 2, 0),
('Super Hero Mask', 'face', 'avatar/face/mask.png', 80, 4, 1),

-- Clothing
('Blue T-Shirt', 'clothing', 'avatar/clothing/blue_tshirt.png', 0, 1, 0),
('Pink Dress', 'clothing', 'avatar/clothing/pink_dress.png', 30, 1, 0),
('Super Hero Cape', 'clothing', 'avatar/clothing/cape.png', 150, 4, 1),
('Wizard Robe', 'clothing', 'avatar/clothing/wizard_robe.png', 120, 3, 1),
('Space Suit', 'clothing', 'avatar/clothing/space_suit.png', 200, 5, 1),

-- Accessories
('Red Cap', 'accessory', 'avatar/accessory/red_cap.png', 10, 1, 0),
('Crown', 'accessory', 'avatar/accessory/crown.png', 50, 3, 0),
('Magic Wand', 'accessory', 'avatar/accessory/wand.png', 60, 3, 1),
('Pet Cat', 'accessory', 'avatar/accessory/pet_cat.png', 100, 4, 1),
('Dragon', 'accessory', 'avatar/accessory/dragon.png', 200, 5, 1),

-- Backgrounds
('Blue Sky', 'background', 'avatar/background/blue_sky.png', 0, 1, 0),
('Green Field', 'background', 'avatar/background/green_field.png', 20, 1, 0),
('Space', 'background', 'avatar/background/space.png', 50, 2, 0),
('Underwater', 'background', 'avatar/background/underwater.png', 60, 3, 0),
('Castle', 'background', 'avatar/background/castle.png', 100, 5, 1);

-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('site_name', 'Pamikil Learning', 'string', 'Name of the learning platform', 1),
('site_description', 'Fun and engaging learning for kids!', 'string', 'Site description', 1),
('coins_per_level', '10', 'number', 'Coins awarded per level up', 0),
('xp_per_level', '50', 'number', 'XP required for each level', 0),
('session_timeout', '1800', 'number', 'Session timeout in seconds', 0),
('max_daily_activities', '20', 'number', 'Maximum activities per day before cooldown', 0),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode', 0),
('welcome_message', 'Welcome to Pamikil Learning! Have fun and learn something new today!', 'string', 'Welcome message displayed to users', 1);

-- ============================================================
-- CREATE WEEKLY LEADERBOARD
-- ============================================================
INSERT INTO `leaderboards` (`leaderboard_type`, `period_start`, `period_end`, `is_active`) VALUES
('weekly_xp', DATE_SUB(CURRENT_DATE, INTERVAL WEEKDAY(CURRENT_DATE) DAY), DATE_ADD(DATE_SUB(CURRENT_DATE, INTERVAL WEEKDAY(CURRENT_DATE) DAY), INTERVAL 6 DAY), 1);

-- ============================================================
-- PARENT SETTINGS FOR SAMPLE PARENT
-- ============================================================
INSERT INTO `parent_settings` (`parent_id`, `child_id`, `daily_time_limit`, `notification_email`) VALUES
((SELECT id FROM users WHERE username='parent1'), (SELECT id FROM users WHERE username='student1'), 120, 1),
((SELECT id FROM users WHERE username='parent1'), (SELECT id FROM users WHERE username='student2'), 120, 1);

-- ============================================================
-- GRANT INITIAL AVATAR ITEMS TO USERS
-- ============================================================
INSERT INTO `user_owned_avatar_items` (`user_id`, `item_id`)
SELECT 
    u.id, 
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'hair' LIMIT 1)
FROM users u WHERE u.role = 'student';

INSERT INTO `user_owned_avatar_items` (`user_id`, `item_id`)
SELECT 
    u.id, 
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'face' LIMIT 1)
FROM users u WHERE u.role = 'student';

INSERT INTO `user_owned_avatar_items` (`user_id`, `item_id`)
SELECT 
    u.id, 
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'clothing' LIMIT 1)
FROM users u WHERE u.role = 'student';

INSERT INTO `user_owned_avatar_items` (`user_id`, `item_id`)
SELECT 
    u.id, 
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'background' LIMIT 1)
FROM users u WHERE u.role = 'student';

-- ============================================================
-- CREATE AVATAR SETTINGS FOR STUDENTS
-- ============================================================
INSERT INTO `user_avatar_settings` (`user_id`, `hair_id`, `face_id`, `clothing_id`, `background_id`)
SELECT 
    u.id,
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'hair' LIMIT 1),
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'face' LIMIT 1),
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'clothing' LIMIT 1),
    (SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'background' LIMIT 1)
FROM users u WHERE u.role = 'student';

-- ============================================================
-- SAMPLE COMPLETED ACTIVITIES FOR TESTING
-- ============================================================
INSERT INTO `user_activity_progress` (`user_id`, `activity_id`, `attempts_count`, `best_score`, `completed`, `last_attempt_at`, `completed_at`, `star_rating`)
SELECT 
    (SELECT id FROM users WHERE username='student1'),
    (SELECT id FROM activities WHERE slug='count-apples'),
    1, 100, 1, NOW(), NOW(), 5;

-- ============================================================
-- END OF SAMPLE DATA
-- ============================================================
