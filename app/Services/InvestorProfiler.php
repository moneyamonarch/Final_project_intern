<?php

namespace App\Services;

class InvestorProfiler
{
    /** Turn stored profile answers into a readable, explainable investor profile. */
    public function build(array $a, array $cfg): array
    {
        $opts = static fn (string $key): array => collect($cfg['profile_steps'])
            ->flatMap(fn ($s) => $s['questions'])->firstWhere('key', $key)['options'] ?? [];
        $label = static fn (string $key, $v): ?string => $v === null ? null : ($opts($key)[$v] ?? null);

        $currency = $a['currency'] ?? null;
        $amount = isset($a['amount']) && is_numeric($a['amount']) ? (float) $a['amount'] : null;
        $budgetNpr = $amount === null ? null : ($currency === 'USD' ? $amount * (float) config('recommender.usd_to_npr', 133) : $amount);

        $risk = $this->risk($a);
        $answered = count(array_filter(array_diff_key($a, ['schema_version' => 1]), static fn ($v) => $v !== null && $v !== ''));

        $profile = [
            'type' => $a['investor_type'] ?? null,
            'type_label' => $label('investor_type', $a['investor_type'] ?? null),
            'origin_label' => $label('origin', $a['origin'] ?? null),
            'experience_label' => $label('experience', $a['experience'] ?? null),
            'structure_label' => $label('structure', $a['structure'] ?? null),
            'horizon_label' => $label('horizon', $a['horizon'] ?? null),
            'budget_npr' => $budgetNpr,
            'budget_label' => $amount === null ? null : ($currency ?: 'NPR').' '.number_format($amount),
            'impact_tilt' => isset($a['impact_tilt']) && is_numeric($a['impact_tilt']) ? (int) $a['impact_tilt'] : null,
            'risk' => $risk,
            'behavior' => $this->behavior($a),
            'answered' => $answered,
        ];
        $profile['chips'] = array_values(array_filter([
            $profile['type_label'], $profile['budget_label'], $profile['horizon_label'], $profile['structure_label'],
            $risk ? $risk['band'].' risk profile' : null,
            $profile['impact_tilt'] === null ? null : ($profile['impact_tilt'] >= 60 ? 'Impact-led' : ($profile['impact_tilt'] <= 40 ? 'Returns-led' : 'Balanced impact/returns')),
        ]));

        return $profile;
    }

    /** Mean of answered risk items (1-5) rescaled to 0-100; null if none answered. */
    public function risk(array $a): ?array
    {
        $items = array_filter([$a['risk_loss'] ?? null, $a['risk_gamble'] ?? null, $a['risk_comfort'] ?? null], 'is_numeric');
        if ($items === []) {
            return null;
        }
        $score = (int) round(((array_sum($items) / count($items)) - 1) / 4 * 100);
        $conflict = isset($a['risk_loss'], $a['risk_gamble']) && abs($a['risk_loss'] - $a['risk_gamble']) >= 3;

        return [
            'score' => $score,
            'band' => $score < 35 ? 'Conservative' : ($score <= 65 ? 'Balanced' : 'Growth'),
            'confidence' => $conflict || count($items) < 2 ? 'low' : 'normal',
        ];
    }

    public function behavior(array $a): array { $likert=static fn($v)=>is_numeric($v)?(int)round(((float)$v-1)/4*100):null; $patience=['1'=>100,'2'=>67,'3'=>33,'4'=>0];$fact=$a['calib_fact']??null;$conf=$likert($a['calib_conf']??null);$scores=['loss_aversion'=>$likert($a['loss_sting']??null),'time_preference'=>$patience[(string)($a['patience']??'')]??null,'herding'=>$likert($a['herding']??null),'overconfidence'=>($fact!==null&&$fact!==''&&$conf!==null)?($fact===config('behavioral.calibration_answer','b')?0:$conf):null];$notes=[];$high=(int)config('behavioral.high',67);$low=(int)config('behavioral.low',33);foreach($scores as $dim=>$score){$texts=config("behavioral.texts.{$dim}",[]);if($score!==null&&$score>=$high&&isset($texts['high']))$notes[]=$texts['high'];elseif($score!==null&&$score<=$low&&isset($texts['low']))$notes[]=$texts['low'];}return $scores+['notes'=>$notes,'answered'=>count(array_filter($scores,fn($v)=>$v!==null))];}
    public function lambda(array $profile): float { $cautious=($profile['risk']['band']??'')==='Conservative'||(($profile['behavior']['loss_aversion']??null)!==null&&$profile['behavior']['loss_aversion']>=(int)config('behavioral.high',67));return (float)config('behavioral.lambda.'.($cautious?'cautious':'base'),$cautious?0.5:0.7);}

    /** Adjust base criterion weights by investor type preset and the impact slider. */
    public function weights(array $base, array $profile): array
    {
        $w = array_merge($base, config('recommender.type_weights.'.($profile['type'] ?? ''), []));
        if ($profile['impact_tilt'] !== null) {
            $k = $profile['impact_tilt'] / 100;
            $w['topic'] = ($w['topic'] ?? 0) * (0.5 + $k);
            $w['focus_area'] = ($w['focus_area'] ?? 0) * (0.5 + $k);
            $w['amount_fit'] = ($w['amount_fit'] ?? 0) * (1.5 - $k);
        }

        return $w;
    }
}