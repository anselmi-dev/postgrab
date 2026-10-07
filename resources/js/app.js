import gsap from 'gsap'
import { animate } from 'motion'

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

if (! reduced) {
    gsap.from('[data-reveal]', {
        y: 18,
        autoAlpha: 0,
        duration: 0.7,
        stagger: 0.08,
        ease: 'power3.out',
        clearProps: 'transform,opacity,visibility',
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
