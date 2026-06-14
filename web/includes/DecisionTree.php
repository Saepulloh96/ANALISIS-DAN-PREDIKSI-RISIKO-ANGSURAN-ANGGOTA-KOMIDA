<?php
/**
 * Decision Tree Classifier in Pure PHP
 * Implements CART (Classification and Regression Trees) using Gini Impurity
 * Supports both continuous (numerical) and categorical features.
 * Matches the JSON export format of the Python scikit-learn training script.
 */

class DecisionTree {
    private $maxDepth;
    private $minSamplesLeaf;
    private $tree;
    private $classes = [];
    private $featureNames = [];

    public function __construct($maxDepth = 5, $minSamplesLeaf = 10) {
        $this->maxDepth = $maxDepth;
        $this->minSamplesLeaf = $minSamplesLeaf;
    }

    /**
     * Train the model
     * @param array $dataset List of associative arrays
     * @param array $featureNames List of column names to use as features
     * @param string $labelCol Column name for the target label
     */
    public function train($dataset, $featureNames, $labelCol) {
        $this->featureNames = $featureNames;
        
        // Extract classes
        $labels = array_column($dataset, $labelCol);
        $this->classes = array_values(array_unique($labels));
        sort($this->classes);

        $this->tree = $this->buildTree($dataset, $featureNames, $labelCol, 0);
        return $this->tree;
    }

    /**
     * Predict the class of a single record
     */
    public function predict($record) {
        if (!$this->tree) {
            throw new Exception("Model has not been trained yet.");
        }
        return $this->predictNode($this->tree, $record);
    }

    private function predictNode($node, $record) {
        if ($node['type'] === 'leaf') {
            return [
                'class' => $node['class'],
                'probability' => $node['probability'],
                'samples' => $node['samples']
            ];
        }

        $feature = $node['feature'];
        $val = $record[$feature] ?? null;
        $threshold = $node['threshold'];

        // Split categorical vs numerical
        if (is_numeric($val) && is_numeric($threshold)) {
            if ($val <= $threshold) {
                return $this->predictNode($node['left'], $record);
            } else {
                return $this->predictNode($node['right'], $record);
            }
        } else {
            // Categorical split (exact match or equality)
            if ($val == $threshold) {
                return $this->predictNode($node['left'], $record);
            } else {
                return $this->predictNode($node['right'], $record);
            }
        }
    }

    /**
     * Calculate Gini Impurity for a subset of labels
     */
    private function calculateGini($labels) {
        $total = count($labels);
        if ($total === 0) return 0;

        $counts = array_count_values($labels);
        $impurity = 1.0;
        foreach ($counts as $class => $count) {
            $p = $count / $total;
            $impurity -= ($p * $p);
        }
        return $impurity;
    }

    /**
     * Build the decision tree recursively
     */
    private function buildTree($dataset, $featureNames, $labelCol, $depth) {
        $labels = array_column($dataset, $labelCol);
        $totalSamples = count($dataset);

        // 1. Get majority class and probabilities for leaf node creation
        $counts = array_count_values($labels);
        arsort($counts);
        $majorityClass = count($counts) > 0 ? array_key_first($counts) : 'Unknown';
        $majorityCount = $counts[$majorityClass] ?? 0;
        $probability = $totalSamples > 0 ? $majorityCount / $totalSamples : 0;

        // Stop conditions:
        // - Pure node (Gini is 0)
        // - Max depth reached
        // - Too few samples
        // - No features left
        if ($this->calculateGini($labels) == 0 || $depth >= $this->maxDepth || $totalSamples < $this->minSamplesLeaf * 2 || empty($featureNames)) {
            return [
                'type' => 'leaf',
                'class' => $majorityClass,
                'probability' => $probability,
                'samples' => $totalSamples
            ];
        }

        // Find the best split
        $bestSplit = $this->findBestSplit($dataset, $featureNames, $labelCol);
        if (!$bestSplit) {
            return [
                'type' => 'leaf',
                'class' => $majorityClass,
                'probability' => $probability,
                'samples' => $totalSamples
            ];
        }

        // Split the dataset
        $leftSet = [];
        $rightSet = [];
        $feature = $bestSplit['feature'];
        $threshold = $bestSplit['threshold'];

        foreach ($dataset as $row) {
            $val = $row[$feature];
            if (is_numeric($val) && is_numeric($threshold)) {
                if ($val <= $threshold) {
                    $leftSet[] = $row;
                } else {
                    $rightSet[] = $row;
                }
            } else {
                if ($val == $threshold) {
                    $leftSet[] = $row;
                } else {
                    $rightSet[] = $row;
                }
            }
        }

        // Recursively build children
        $leftBranch = $this->buildTree($leftSet, $featureNames, $labelCol, $depth + 1);
        $rightBranch = $this->buildTree($rightSet, $featureNames, $labelCol, $depth + 1);

        return [
            'type' => 'split',
            'feature' => $feature,
            'threshold' => $threshold,
            'left' => $leftBranch,
            'right' => $rightBranch
        ];
    }

    /**
     * Find best split using Gini Impurity
     */
    private function findBestSplit($dataset, $featureNames, $labelCol) {
        $bestGini = 999.0;
        $bestSplit = null;
        $total = count($dataset);
        $labels = array_column($dataset, $labelCol);
        $parentGini = $this->calculateGini($labels);

        foreach ($featureNames as $feature) {
            // Get unique values for this feature
            $values = array_column($dataset, $feature);
            $uniqueValues = array_unique($values);
            sort($uniqueValues);

            // Determine candidate thresholds
            $thresholds = [];
            $isNumeric = true;
            foreach ($uniqueValues as $v) {
                if (!is_numeric($v)) {
                    $isNumeric = false;
                    break;
                }
            }

            if ($isNumeric) {
                // For numeric features, use midpoints
                $countVals = count($uniqueValues);
                if ($countVals > 1) {
                    for ($i = 0; $i < $countVals - 1; $i++) {
                        $thresholds[] = ($uniqueValues[$i] + $uniqueValues[$i+1]) / 2;
                    }
                } else {
                    $thresholds[] = $uniqueValues[0] ?? 0;
                }
            } else {
                // For categorical, split each value vs others
                $thresholds = $uniqueValues;
            }

            // Test each threshold
            foreach ($thresholds as $threshold) {
                $leftLabels = [];
                $rightLabels = [];
                
                foreach ($dataset as $row) {
                    $val = $row[$feature];
                    $lbl = $row[$labelCol];
                    if ($isNumeric) {
                        if ($val <= $threshold) {
                            $leftLabels[] = $lbl;
                        } else {
                            $rightLabels[] = $lbl;
                        }
                    } else {
                        if ($val == $threshold) {
                            $leftLabels[] = $lbl;
                        } else {
                            $rightLabels[] = $lbl;
                        }
                    }
                }

                $nLeft = count($leftLabels);
                $nRight = count($rightLabels);

                if ($nLeft < $this->minSamplesLeaf || $nRight < $this->minSamplesLeaf) {
                    continue; // Skip small nodes
                }

                $giniLeft = $this->calculateGini($leftLabels);
                $giniRight = $this->calculateGini($rightLabels);
                $splitGini = ($nLeft / $total) * $giniLeft + ($nRight / $total) * $giniRight;

                if ($splitGini < $bestGini) {
                    $bestGini = $splitGini;
                    $bestSplit = [
                        'feature' => $feature,
                        'threshold' => $threshold,
                        'gini' => $splitGini
                    ];
                }
            }
        }

        // Only split if we reduce impurity
        return ($bestGini < $parentGini) ? $bestSplit : null;
    }

    /**
     * Load model from JSON representation
     */
    public function loadModel($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Model file not found: " . $filePath);
        }
        $data = json_decode(file_get_contents($filePath), true);
        if (!$data || !isset($data['tree'])) {
            throw new Exception("Invalid model file format.");
        }
        $this->tree = $data['tree'];
        $this->classes = $data['classes'] ?? ['Lancar', 'Diragukan', 'Macet'];
        return $data;
    }

    /**
     * Save model as JSON file containing tree and metrics
     */
    public function saveModel($filePath, $dataset, $labelCol) {
        $predictions = [];
        $actuals = [];
        foreach ($dataset as $row) {
            $pred = $this->predict($row);
            $predictions[] = $pred['class'];
            $actuals[] = $row[$labelCol];
        }

        // Calculate metrics
        $correct = 0;
        $total = count($actuals);
        for ($i = 0; $i < $total; $i++) {
            if ($predictions[$i] === $actuals[$i]) $correct++;
        }
        $accuracy = $total > 0 ? $correct / $total : 0;

        // Build confusion matrix
        $matrix = [
            'Lancar' => ['Lancar' => 0, 'Diragukan' => 0, 'Macet' => 0],
            'Diragukan' => ['Lancar' => 0, 'Diragukan' => 0, 'Macet' => 0],
            'Macet' => ['Lancar' => 0, 'Diragukan' => 0, 'Macet' => 0]
        ];

        for ($i = 0; $i < $total; $i++) {
            $act = $actuals[$i];
            $pred = $predictions[$i];
            if (isset($matrix[$act][$pred])) {
                $matrix[$act][$pred]++;
            }
        }

        $confusionMatrix = [
            [$matrix['Lancar']['Lancar'], $matrix['Lancar']['Diragukan'], $matrix['Lancar']['Macet']],
            [$matrix['Diragukan']['Lancar'], $matrix['Diragukan']['Diragukan'], $matrix['Diragukan']['Macet']],
            [$matrix['Macet']['Lancar'], $matrix['Macet']['Diragukan'], $matrix['Macet']['Macet']]
        ];

        // Classification report calculations
        $report = [];
        foreach ($this->classes as $c) {
            $tp = $matrix[$c][$c] ?? 0;
            
            $totalAct = 0;
            foreach ($matrix[$c] as $p => $cnt) $totalAct += $cnt;
            
            $totalPred = 0;
            foreach ($matrix as $a => $pRow) $totalPred += $pRow[$c];

            $precision = $totalPred > 0 ? $tp / $totalPred : 0;
            $recall = $totalAct > 0 ? $tp / $totalAct : 0;
            $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

            $report[$c] = [
                'precision' => $precision,
                'recall' => $recall,
                'f1-score' => $f1,
                'support' => $totalAct
            ];
        }

        // Rough feature importances (number of splits on that feature)
        $importances = [];
        foreach ($this->featureNames as $f) {
            $importances[$f] = 0.0;
        }
        $this->countSplits($this->tree, $importances);
        $totalSplits = array_sum($importances);
        if ($totalSplits > 0) {
            foreach ($importances as $f => $cnt) {
                $importances[$f] = $cnt / $totalSplits;
            }
        }

        $modelData = [
            'accuracy' => $accuracy,
            'classes' => $this->classes,
            'confusion_matrix' => $confusionMatrix,
            'classification_report' => $report,
            'feature_importances' => $importances,
            'tree' => $this->tree
        ];

        file_put_contents($filePath, json_encode($modelData, JSON_PRETTY_PRINT));
        return $modelData;
    }

    private function countSplits($node, &$importances) {
        if ($node['type'] === 'leaf') return;
        $feat = $node['feature'];
        if (isset($importances[$feat])) {
            $importances[$feat]++;
        }
        $this->countSplits($node['left'], $importances);
        $this->countSplits($node['right'], $importances);
    }
}
