// ============================================
// NutriFit — Flash Message Component
// Replaces PHP flash session messages
// ============================================

import { useState, useEffect } from 'react';

export default function FlashMessage({ type, message, onClose }) {
    useEffect(() => {
        if (message) {
            const timer = setTimeout(() => {
                if (onClose) onClose();
            }, 4000);
            return () => clearTimeout(timer);
        }
    }, [message, onClose]);

    if (!message) return null;

    return (
        <div className={`flash flash-${type}`}>
            <div className="container">
                <i className={`fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}`}></i>
                {' '}{message}
            </div>
        </div>
    );
}
