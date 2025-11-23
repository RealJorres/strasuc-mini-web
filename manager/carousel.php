<?php
session_start();

echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">';

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../manager/config/config.php';
require_once '../manager/includes/api_client.php';
require_once '../manager/includes/functions.php';

class SportsViewerData {
    private $apiClient;
    public function __construct($apiClient) { $this->apiClient = $apiClient; }
    public function fetchData($endpoint) {
        try {
            $response = $this->apiClient->get($endpoint);
            return $response['success'] ? ($response['data'] ?? []) : [];
        } catch (Exception $e) {
            error_log("Error fetching {$endpoint}: " . $e->getMessage());
            return [];
        }
    }
    public function getAllData() {
        $endpoints = [
            'universities'=>'/universities',
            'medalTally'=>'/medals/tally',
            'schedules'=>'/schedules',
            'sports'=>'/sports',
            'participants'=>'/participants',
            'teams'=>'/teams',
            'scores'=>'/scores'
        ];
        $data = [];
        foreach ($endpoints as $key=>$endpoint) {
            $data[$key] = $this->fetchData($endpoint);
        }
        return $data;
    }
}

$dataFetcher = new SportsViewerData($apiClient);
$appData = $dataFetcher->getAllData();

function escape($data) { return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'); }
$selectedUniversity = $_GET['university'] ?? 'all';

$processedMedalTally = [];
if (!empty($appData['medalTally'])) {
    $processedMedalTally = array_map(function($tally) use ($appData) {
        $university = array_filter($appData['universities'], fn($u)=>($u['id']==$tally['university_id'])||($u['name']==$tally['university_name'])||($u['abbreviation']==$tally['university_name']));
        $university = !empty($university) ? reset($university) : null;
        return [
            'university_id'=>$tally['university_id'] ?? $tally['universityId'] ?? $tally['id'] ?? null,
            'university_name'=>$tally['university_name'] ?? $tally['universityName'] ?? $tally['name'] ?? '',
            'university'=>$university,
            'golds'=>intval($tally['golds'] ?? $tally['gold'] ?? $tally['gold_count'] ?? 0),
            'silvers'=>intval($tally['silvers'] ?? $tally['silver'] ?? $tally['silver_count'] ?? 0),
            'bronzes'=>intval($tally['bronzes'] ?? $tally['bronze'] ?? $tally['bronze_count'] ?? 0)
        ];
    }, $appData['medalTally']);

    usort($processedMedalTally, function($a,$b){
        if($b['golds']!==$a['golds']) return $b['golds']-$a['golds'];
        if($b['silvers']!==$a['silvers']) return $b['silvers']-$a['silvers'];
        return $b['bronzes']-$a['bronzes'];
    });
}

function getScheduleParticipants($schedule,$participants,$teams,$universities){
    $scheduleParticipants = array_filter($participants, fn($p)=>$p['schedule_id']==$schedule['id']);
    $participantUniversities=[];
    foreach($scheduleParticipants as $participant){
        if($participant['team_id']){
            $team = array_filter($teams, fn($t)=>$t['id']==$participant['team_id']);
            $team = !empty($team)?reset($team):null;
            if($team && $team['university_id']){
                $university = array_filter($universities, fn($u)=>$u['id']==$team['university_id']);
                $university = !empty($university)?reset($university):null;
                if($university) $participantUniversities[$university['id']]=$university;
            }
        }
    }
    return $participantUniversities;
}

$processedSchedules=['ongoing'=>[],'upcoming'=>[],'all'=>[]];
if(!empty($appData['schedules'])){
    foreach($appData['schedules'] as $schedule){
        $participantUniversities = getScheduleParticipants($schedule,$appData['participants']??[],$appData['teams']??[],$appData['universities']??[]);
        $schedule['participant_universities']=$participantUniversities;
        $showSchedule = $selectedUniversity==='all' || array_key_exists($selectedUniversity,$participantUniversities);
        if($showSchedule){
            $processedSchedules['all'][]=$schedule;
            if(($schedule['status']??'')==='ongoing') $processedSchedules['ongoing'][]=$schedule;
            elseif(($schedule['status']??'')==='scheduled') $processedSchedules['upcoming'][]=$schedule;
        }
    }
}

$jsonData=json_encode([
    'universities'=>$appData['universities']??[],
    'medalTally'=>$appData['medalTally']??[],
    'schedules'=>$appData['schedules']??[],
    'sports'=>$appData['sports']??[],
    'participants'=>$appData['participants']??[],
    'teams'=>$appData['teams']??[],
    'scores'=>$appData['scores']??[]
], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>STRASUC - Sports Festival Viewer</title>
<link rel="shortcut icon" href="../manager/assets/img/icon.png" type="image/x-icon">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
body{font-family:'Poppins',sans-serif;background:#f8f9fa;}
.bento-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;}
.bento-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden;transition:transform 0.2s;}
.bento-card:hover{transform:translateY(-4px);}
.bento-card .card-body{padding:1rem;}
.medal-badge{display:inline-block;min-width:36px;text-align:center;padding:6px 8px;border-radius:6px;color:#fff;font-weight:700;}
.medal-gold{background:#FFD700;}
.medal-silver{background:#C0C0C0;}
.medal-bronze{background:#CD7F32;}
.status-indicator{padding:4px 8px;border-radius:6px;font-weight:700;color:#fff;font-size:0.8rem;}
.status-ongoing{background:#dc3545;}
.status-scheduled{background:#0d6efd;}
.status-ended{background:#198754;}
.status-cancelled{background:#6c757d;}
.university-badge{font-size:0.75rem;margin:1px;}
.fade-in{animation:fadeIn 0.5s ease-in;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
.sport-image{width:40px;height:40px;object-fit:contain;}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark">
<div class="container">
<a class="navbar-brand text-white" href="#"><img src="../manager/assets/img/main_logo_small.png" style="height:40px;" alt="Logo"></a>
<div class="navbar-nav flex-row gap-2">
<a class="nav-link text-white" href="#medal-tally">Medal Tally</a>
<a class="nav-link text-white" href="#live-scores">Live Scores</a>
<a class="nav-link text-white" href="#schedules">Schedules</a>
</div>
</div>
</nav>

<div class="container py-4">

<!-- Medal Tally -->
<section id="medal-tally" class="mb-5">
<h2 class="mb-3"><i class="fas fa-trophy me-2"></i>University Medal Tally</h2>
<div class="bento-grid">
<?php if(empty($processedMedalTally)): ?>
<div class="bento-card text-center p-4">No medal data available</div>
<?php else: foreach($processedMedalTally as $index=>$tally): ?>
<div class="bento-card fade-in">
<div class="card-body text-center">
<?php if($tally['university']): ?>
<img src="../manager/assets/img/sucs_logo/<?= escape($tally['university']['abbreviation']??'unknown') ?>.png" class="sport-image mb-2" alt="<?= escape($tally['university']['name']) ?>" onerror="this.src='../manager/assets/img/sports_icon/default.png'">
<?php endif; ?>
<h6 class="fw-bold"><?= escape($tally['university']['name']??$tally['university_name']) ?></h6>
<div class="medal-count mt-2">
<span class="medal-badge medal-gold"><?= $tally['golds'] ?></span>
<span class="medal-badge medal-silver"><?= $tally['silvers'] ?></span>
<span class="medal-badge medal-bronze"><?= $tally['bronzes'] ?></span>
</div>
<div class="badge bg-primary mt-2">#<?= $index+1 ?></div>
</div>
</div>
<?php endforeach; endif; ?>
</div>
</section>

<!-- Live Scores -->
<section id="live-scores" class="mb-5">
<h2 class="mb-3"><i class="fas fa-running me-2"></i>Live Scores</h2>
<div class="bento-grid" id="liveScoresContainer">
<div class="bento-card text-center p-4"><div class="spinner-border text-primary"></div> Loading...</div>
</div>
</section>

<!-- Schedules -->
<section id="schedules" class="mb-5">
<h2 class="mb-3"><i class="fas fa-calendar me-2"></i>Match Schedules</h2>
<div class="bento-grid">
<!-- Ongoing and Upcoming Matches -->
<?php foreach(['ongoing'=>'Ongoing Now','upcoming'=>'Starting Soon'] as $key=>$title): ?>
<div class="bento-card">
<div class="card-body">
<h6><?= $title ?> (<?= count($processedSchedules[$key]) ?>)</h6>
<?php if(empty($processedSchedules[$key])): ?>
<p class="text-muted">No matches</p>
<?php else: foreach($processedSchedules[$key] as $schedule):
$sport=array_filter($appData['sports']??[],fn($s)=>$s['id']==$schedule['sport_id']); $sport=!empty($sport)?reset($sport):null;
?>
<div class="d-flex align-items-center mb-2 p-2 border rounded fade-in">
<img src="../manager/assets/img/sports_icon/<?= $sport['name']??'default' ?>.png" class="sport-image me-2" onerror="this.src='../manager/assets/img/sports_icon/default.png'">
<div class="flex-grow-1">
<div class="fw-semibold"><?= escape($schedule['event_name']) ?></div>
<small class="text-muted"><?= date('H:i',strtotime($schedule['date'])) ?> • <?= escape($schedule['venue']) ?></small>
<div class="mt-1">
<?php foreach($schedule['participant_universities'] as $uni): ?>
<span class="badge bg-secondary university-badge"><?= escape($uni['abbreviation']??$uni['name']) ?></span>
<?php endforeach; ?>
</div>
</div>
<span class="status-indicator status-<?= $key==='ongoing'?'ongoing':'scheduled' ?>">LIVE</span>
</div>
<?php endforeach; endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
</section>

</div>

<footer class="bg-dark text-light py-4 text-center">
<p class="mb-0">&copy; <?= date('Y') ?> STRASUC Sports Festival</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let appData=<?= $jsonData ?>;
const API_BASE_URL='http://localhost:5000/api/v1';
async function safeFetchJson(url){const r=await fetch(url);if(!r.ok)throw new Error(`HTTP ${r.status}`);const j=await r.json();return j.data??j;}
async function fetchMedalTally(){try{return await safeFetchJson(`${API_BASE_URL}/medals/tally`);}catch(e){console.error(e);return[];}}
async function fetchSchedules(){try{return await safeFetchJson(`${API_BASE_URL}/schedules`);}catch(e){console.error(e);return[];}}
async function fetchLiveScores(){try{return await safeFetchJson(`${API_BASE_URL}/scores`);}catch(e){console.error(e);return[];}}
document.addEventListener('DOMContentLoaded',async()=>{await loadAllData();startAutoRefresh();});
async function loadAllData(){try{const[m,s,c]=await Promise.all([fetchMedalTally(),fetchSchedules(),fetchLiveScores()]);appData.medalTally=m;appData.schedules=s;appData.liveScores=c;loadLiveScores();}catch(e){console.error(e);}}
function loadLiveScores(){const c=document.getElementById('liveScoresContainer');const ongoing=appData.schedules.filter(s=>s.status==='ongoing');if(!ongoing.length){c.innerHTML='<div class="bento-card text-center p-4">No live matches</div>';return;}
c.innerHTML=ongoing.map(schedule=>{const sport=appData.sports.find(s=>Number(s.id)===Number(schedule.sport_id));const scoresForSchedule=appData.liveScores.filter(sc=>Number(sc.schedule_id)===Number(schedule.id)).sort((a,b)=>b.score-a.score);const rows=scoresForSchedule.length>0?scoresForSchedule.map(sc=>{const participant=appData.participants.find(p=>Number(p.id)===Number(sc.participant_id))||{};const team=appData.teams.find(t=>Number(t.id)===Number(participant.team_id));const uni=team?appData.universities.find(u=>Number(u.id)===Number(team.university_id)):null;const teamName=uni?.team||participant.name||'Team';const uniName=uni?.name||'';return`<div class="row align-items-center mb-2"><div class="col-8 text-start fw-medium">${escapeHtml(teamName)}<br><small class="text-muted">${escapeHtml(uniName)}</small></div><div class="col-4 text-end fs-5 fw-bold">${Number(sc.score)}</div></div>`;}).join(''):'<div class="text-center text-muted">Scores not available</div>';return`<div class="bento-card fade-in"><div class="card-body d-flex flex-column"><div class="d-flex align-items-center mb-2"><img src="../manager/assets/img/sports_icon/${(sport.name||'default').toLowerCase().replace(/\s+/g,'_')}.png" class="sport-image me-2">${escapeHtml(schedule.event_name)}</div>${rows}<small class="text-muted mt-2">Match time: ${formatTime(schedule.date)}</small></div></div>`;}).join('');}
function escapeHtml(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'):"";}
function startAutoRefresh(){setInterval(async()=>{await loadAllData();},15000);}
function formatTime(d){return new Date(d).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});}
document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',function(e){e.preventDefault();document.querySelector(this.getAttribute('href'))?.scrollIntoView({behavior:'smooth'});}));
</script>
</body>
</html>
