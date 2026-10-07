import gsap from 'gsap'
import { animate } from 'motion'

window.__revealStarted = true

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

const revealed = document.querySelectorAll('[data-reveal]')
const finishReveal = () => {
    document.documentElement.classList.remove('motion')
    gsap.set(revealed, { clearProps: 'transform,opacity,visibility' })
}

if (! reduced && revealed.length && document.documentElement.classList.contains('motion')) {
    const fallback = window.setTimeout(finishReveal, 1500)

    gsap.fromTo(revealed, {
        y: 18,
        autoAlpha: 0,
    }, {
        y: 0,
        autoAlpha: 1,
        duration: 0.7,
        stagger: 0.08,
        ease: 'power3.out',
        onComplete() {
            window.clearTimeout(fallback)
            finishReveal()
        },
    })

    document.querySelectorAll('[data-motion="pop"]').forEach((element) => {
        element.addEventListener('pointerenter', () => {
            animate(element, { scale: 1.08 }, { type: 'spring', stiffness: 420, damping: 18 })
        })

        element.addEventListener('pointerleave', () => {
            animate(element, { scale: 1 }, { type: 'spring', stiffness: 420, damping: 24 })
        })
    })
}
