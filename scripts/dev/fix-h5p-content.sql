-- Fix H5P content for Lesson 1: Vocales
-- This updates the H5P content to use the correct MultiChoice format

UPDATE wp_h5p_contents 
SET 
    parameters = '{"question":"¿Cuáles son las vocales en Tamazight?","answers":[{"text":"a","correct":true},{"text":"i","correct":true},{"text":"u","correct":true},{"text":"e","correct":true},{"text":"o","correct":true}],"UI":{"scoreBarLabel":"Puntuación: @score de @total","checkAnswerButton":"Comprobar","retryButton":"Reintentar","showSolutionButton":"Mostrar solución","noAnswerMessage":"No has seleccionado ninguna respuesta","confirmRetry":"¿Seguro que quieres volver a intentarlo?"},"behaviour":{"enableRetry":true,"enableSolutionsButton":true,"singleAnswer":false}}',
    filtered = ''
WHERE id = 24;

-- Clear H5P cached assets (run this in bash, not SQL):
-- rm -rf /var/www/html/wp-content/uploads/h5p/cachedassets/*
