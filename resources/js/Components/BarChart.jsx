import { useEffect, useRef } from 'react'

/**
 * Wykres słupkowy (Chart.js) dla Inertia/React.
 * Chart.js dotyka DOM — ładowany dynamicznie w useEffect (SSR-safe).
 */
export default function BarChart({ labels = [], data = [], label = 'Zakupy', color = '#E20074', height = 90 }) {
    const canvasRef = useRef(null)
    const chartRef = useRef(null)

    useEffect(() => {
        let alive = true
        import('chart.js/auto').then(({ default: Chart }) => {
            if (!alive || !canvasRef.current) return
            chartRef.current = new Chart(canvasRef.current, {
                type: 'bar',
                data: { labels, datasets: [{ label, data, backgroundColor: color, borderRadius: 4 }] },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            })
        })
        return () => {
            alive = false
            if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null }
        }
    }, [labels, data, label, color])

    return <canvas ref={canvasRef} height={height} />
}
