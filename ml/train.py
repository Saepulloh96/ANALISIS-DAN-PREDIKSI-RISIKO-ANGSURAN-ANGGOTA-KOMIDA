import os
import json
import pandas as pd
# pyrefly: ignore [missing-import]
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix

def main():
    csv_path = '../data/dataset_komida.csv'
    # Fallback to local run
    if not os.path.exists(csv_path):
        csv_path = 'data/dataset_komida.csv'
        
    if not os.path.exists(csv_path):
        print(f"Error: Dataset {csv_path} not found!")
        return

    # Load data
    df = pd.read_csv(csv_path)
    print(f"Loaded {len(df)} rows from dataset.")
    
    # Feature engineering / selection
    # Numeric features
    num_cols = [
        'jumlah_pinjaman', 'tenor', 'cicilan', 
        'simp_wajib', 'simp_sukarela', 'simp_pensiun', 'simp_hari_raya',
        'total_simpanan', 'rasio_simpanan'
    ]
    
    # Categorical features
    cat_cols = ['tujuan_pinjaman']
    
    # One-hot encode categorical features
    df_encoded = pd.get_dummies(df, columns=cat_cols, drop_first=False)
    
    # Get the engineered columns list
    encoded_cat_cols = [col for col in df_encoded.columns if col.startswith('tujuan_pinjaman_')]
    
    features = num_cols + encoded_cat_cols
    X = df_encoded[features]
    y = df_encoded['label_risiko']
    
    # Split dataset
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)
    
    # Train decision tree
    clf = DecisionTreeClassifier(max_depth=5, min_samples_leaf=10, random_state=42)
    clf.fit(X_train, y_train)
    
    # Predict and evaluate
    y_pred = clf.predict(X_test)
    acc = accuracy_score(y_test, y_pred)
    
    print("\n--- Model Evaluation ---")
    print(f"Accuracy: {acc:.4f}")
    print("\nClassification Report:")
    print(classification_report(y_test, y_pred))
    
    cm = confusion_matrix(y_test, y_pred, labels=['Lancar', 'Diragukan', 'Macet'])
    print("\nConfusion Matrix (Lancar, Diragukan, Macet):")
    print(cm)
    
    # Export structure to json
    tree_rules = build_json_tree(clf, features, clf.classes_)
    
    # Export evaluation metadata
    evaluation_metadata = {
        "accuracy": float(acc),
        "classification_report": classification_report(y_test, y_pred, output_dict=True),
        "confusion_matrix": cm.tolist(),
        "feature_importances": dict(zip(features, [float(x) for x in clf.feature_importances_])),
        "classes": clf.classes_.tolist(),
        "tree": tree_rules
    }
    
    os.makedirs('ml', exist_ok=True)
    model_json_path = 'ml/model.json'
    with open(model_json_path, 'w') as f:
        json.dump(evaluation_metadata, f, indent=4)
        
    print(f"\nModel exported successfully to {model_json_path}")

def build_json_tree(tree, feature_names, class_names):
    tree_ = tree.tree_
    
    def recurse(node):
        if tree_.feature[node] != -2: # Not a leaf
            name = feature_names[tree_.feature[node]]
            threshold = float(tree_.threshold[node])
            left_child = int(tree_.children_left[node])
            right_child = int(tree_.children_right[node])
            
            return {
                "type": "split",
                "feature": name,
                "threshold": threshold,
                "left": recurse(left_child),
                "right": recurse(right_child)
            }
        else: # Leaf
            # Get index of the class with max value
            val = tree_.value[node][0]
            class_idx = np.argmax(val)
            class_label = class_names[class_idx]
            
            # calculate confidence/probability
            total_samples = float(np.sum(val))
            prob = float(val[class_idx] / total_samples) if total_samples > 0 else 0.0
            
            return {
                "type": "leaf",
                "class": class_label,
                "probability": prob,
                "samples": int(total_samples)
            }
            
    return recurse(0)

if __name__ == '__main__':
    main()
