<?php
defined('APP_ACCESS') or die('Direct access not permitted');

class TMDB {
    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBaseUrl = 'https://image.tmdb.org/t/p';
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: TMDB_API_KEY;
    }
    
    private function makeRequest($endpoint, $params = []) {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'TMDB API key not configured'];
        }
        
        $params['api_key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'API request failed with code: ' . $httpCode];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Failed to parse API response'];
        }
        
        return ['success' => true, 'data' => $data];
    }
    
    public function getMovieById($tmdbId) {
        $result = $this->makeRequest("/movie/{$tmdbId}", [
            'append_to_response' => 'credits,videos,images'
        ]);
        
        if (!$result['success']) {
            return $result;
        }
        
        $movie = $result['data'];
        
        $genres = [];
        if (isset($movie['genres'])) {
            foreach ($movie['genres'] as $genre) {
                $genres[] = $genre['name'];
            }
        }
        
        $cast = [];
        if (isset($movie['credits']['cast'])) {
            foreach (array_slice($movie['credits']['cast'], 0, 10) as $actor) {
                $cast[] = $actor['name'];
            }
        }
        
        $director = '';
        if (isset($movie['credits']['crew'])) {
            foreach ($movie['credits']['crew'] as $crew) {
                if ($crew['job'] === 'Director') {
                    $director = $crew['name'];
                    break;
                }
            }
        }
        
        $trailerUrl = '';
        if (isset($movie['videos']['results'])) {
            foreach ($movie['videos']['results'] as $video) {
                if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                    $trailerUrl = 'https://www.youtube.com/watch?v=' . $video['key'];
                    break;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'tmdb_id' => $movie['id'],
                'title' => $movie['title'] ?? '',
                'original_title' => $movie['original_title'] ?? '',
                'description' => $movie['overview'] ?? '',
                'poster' => isset($movie['poster_path']) ? $this->imageBaseUrl . '/w500' . $movie['poster_path'] : '',
                'backdrop' => isset($movie['backdrop_path']) ? $this->imageBaseUrl . '/original' . $movie['backdrop_path'] : '',
                'year' => isset($movie['release_date']) ? (int)date('Y', strtotime($movie['release_date'])) : null,
                'rating' => $movie['vote_average'] ?? 0,
                'runtime' => $movie['runtime'] ?? 0,
                'genres' => implode(', ', $genres),
                'language' => $movie['original_language'] ?? '',
                'country' => isset($movie['production_countries'][0]['name']) ? $movie['production_countries'][0]['name'] : '',
                'director' => $director,
                'cast' => implode(', ', $cast),
                'trailer_url' => $trailerUrl
            ]
        ];
    }
    
    public function searchMovies($query, $page = 1) {
        return $this->makeRequest('/search/movie', [
            'query' => $query,
            'page' => $page
        ]);
    }
    
    public function getPopularMovies($page = 1) {
        return $this->makeRequest('/movie/popular', [
            'page' => $page
        ]);
    }
    
    public function getTrendingMovies($page = 1) {
        return $this->makeRequest('/trending/movie/week', [
            'page' => $page
        ]);
    }
    
    public function getImageUrl($path, $size = 'w500') {
        if (empty($path)) {
            return '';
        }
        return $this->imageBaseUrl . '/' . $size . $path;
    }
}
