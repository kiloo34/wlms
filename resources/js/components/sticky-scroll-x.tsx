import {
    useCallback,
    useEffect,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { cn } from '@/lib/utils';

interface StickyScrollXProps {
    children: ReactNode;
    /** Class untuk container yang di-scroll horizontal (mis. flex + gap). */
    className?: string;
}

/**
 * Container scroll horizontal dengan scrollbar yang menempel (sticky) di bawah
 * layar selama container terlihat. Berguna untuk board Kanban yang tinggi:
 * user tidak perlu scroll ke paling bawah dulu untuk menggeser kolom.
 *
 * Scrollbar asli container disembunyikan; diganti bar "bayangan" yang
 * scrollLeft-nya disinkronkan dua arah dengan container.
 */
export function StickyScrollX({ children, className }: StickyScrollXProps) {
    const contentRef = useRef<HTMLDivElement>(null);
    const barRef = useRef<HTMLDivElement>(null);
    const [scrollWidth, setScrollWidth] = useState(0);
    const [clientWidth, setClientWidth] = useState(0);

    const measure = useCallback(() => {
        const el = contentRef.current;
        if (!el) return;
        setScrollWidth(el.scrollWidth);
        setClientWidth(el.clientWidth);
    }, []);

    useEffect(() => {
        const el = contentRef.current;
        if (!el) return;

        measure();

        // Ukuran container sendiri + tiap kolom (lebar kolom berubah saat
        // kerapatan diganti) memengaruhi scrollWidth.
        const resizeObserver = new ResizeObserver(measure);
        const observeChildren = () => {
            resizeObserver.disconnect();
            resizeObserver.observe(el);
            Array.from(el.children).forEach((child) =>
                resizeObserver.observe(child),
            );
        };
        observeChildren();

        // Kolom ditambah/dihapus (status workflow berubah).
        const mutationObserver = new MutationObserver(() => {
            observeChildren();
            measure();
        });
        mutationObserver.observe(el, { childList: true });

        return () => {
            resizeObserver.disconnect();
            mutationObserver.disconnect();
        };
    }, [measure]);

    const hasOverflow = scrollWidth > clientWidth + 1;

    // Saat bar baru muncul, samakan posisinya dengan container.
    useEffect(() => {
        if (hasOverflow && barRef.current && contentRef.current) {
            barRef.current.scrollLeft = contentRef.current.scrollLeft;
        }
    }, [hasOverflow]);

    // Pengecekan kesamaan mencegah loop sinkronisasi antar dua elemen.
    const handleContentScroll = () => {
        const content = contentRef.current;
        const bar = barRef.current;
        if (content && bar && bar.scrollLeft !== content.scrollLeft) {
            bar.scrollLeft = content.scrollLeft;
        }
    };

    const handleBarScroll = () => {
        const content = contentRef.current;
        const bar = barRef.current;
        if (content && bar && content.scrollLeft !== bar.scrollLeft) {
            content.scrollLeft = bar.scrollLeft;
        }
    };

    return (
        <div>
            <div
                ref={contentRef}
                onScroll={handleContentScroll}
                className={cn(
                    '[scrollbar-width:none] overflow-x-auto [&::-webkit-scrollbar]:hidden',
                    className,
                )}
            >
                {children}
            </div>
            {hasOverflow && (
                <div
                    ref={barRef}
                    onScroll={handleBarScroll}
                    className="bg-background/80 sticky bottom-0 z-10 overflow-x-auto overflow-y-hidden py-1 backdrop-blur-sm"
                    aria-hidden="true"
                >
                    <div style={{ width: scrollWidth, height: 1 }} />
                </div>
            )}
        </div>
    );
}
