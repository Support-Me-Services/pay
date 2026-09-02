import { useState } from 'react'

/** Pole hasła z przyciskiem podglądu wpisywanego tekstu (pokaż/ukryj). */
export default function PasswordInput({ id, value, onChange, ...rest }) {
    const [visible, setVisible] = useState(false)

    return (
        <div style={{ position: 'relative' }}>
            <input
                id={id}
                type={visible ? 'text' : 'password'}
                value={value}
                onChange={onChange}
                style={{ paddingRight: 60, width: '100%', boxSizing: 'border-box' }}
                {...rest}
            />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                tabIndex={-1}
                aria-label={visible ? 'Ukryj hasło' : 'Pokaż hasło'}
                style={{
                    position: 'absolute', right: 4, top: '50%', transform: 'translateY(-50%)',
                    border: 0, background: 'none', cursor: 'pointer', padding: '4px 8px',
                    color: 'var(--brand, #E20074)', fontSize: 12, fontWeight: 600,
                }}
            >
                {visible ? 'Ukryj' : 'Pokaż'}
            </button>
        </div>
    )
}
