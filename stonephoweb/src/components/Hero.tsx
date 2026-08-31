import React, { useEffect, useState } from "react";
import { Star, ChevronDown } from "lucide-react";
import { useLanguage } from "../contexts/LanguageContext";
import { useScrollAnimation } from "../hooks/useScrollAnimation";

interface Review { name: string; text: string; date: string; }
interface Slide {
  id: string;
  type: "main" | "reviews" | "special" | "memorial";
  title?: string;
  subtitle?: string;
  hours?: string;
  buttonText?: string;
  buttonLink?: string;
  reviews?: Review[];
}

const slides: Slide[] = [
  {
    id: "memorial-1",
    type: "memorial",
    title: "Happy July 4th",
    subtitle: "Celebrating freedom with bold flavors and great company.",
    hours: "Open as usual · 11:00 AM – 7:00 PM",
  },
  {
    id: "main-1",
    type: "main",
    title: "Authentic Vietnamese Cuisine",
    subtitle: "Experience the flavors of Vietnam in Valdosta",
    buttonText: "Order Now",
    buttonLink: "https://www.clover.com/online-ordering/stone-pho-valdosta",
  },
  {
    id: "reviews-1",
    type: "reviews",
    reviews: [
      { name: "Sarah M.", text: "Best pho I've had outside of Vietnam! The broth is so rich and flavorful.", date: "2 days ago" },
      { name: "James K.", text: "The spring rolls are amazing! So fresh and delicious.", date: "1 week ago" },
      { name: "Emily R.", text: "Finally, authentic Vietnamese food in Valdosta. Highly recommend!", date: "3 weeks ago" },
    ],
  },
  {
    id: "special-1",
    type: "special",
    title: "Stone Pho Special",
    subtitle: "Our signature dish with rare steak, brisket, tendon, and meatball",
    buttonText: "View Menu",
    buttonLink: "#menu",
  },
];

/* ── Reusable outlined CTA button ──────────────────────────── */
const HeroBtn: React.FC<{
  href: string;
  external?: boolean;
  ghost?: boolean;
  children: React.ReactNode;
}> = ({ href, external, ghost, children }) => {
  const [hov, setHov] = useState(false);
  const gold = !ghost;
  return (
    <a
      href={href}
      {...(external ? { target: "_blank", rel: "noopener noreferrer" } : {})}
      onMouseEnter={() => setHov(true)}
      onMouseLeave={() => setHov(false)}
      style={{
        display: "inline-block",
        padding: "0.9rem 2.25rem",
        border: `1px solid ${gold ? "#C9A96E" : "rgba(245,237,216,0.3)"}`,
        color: hov
          ? gold ? "#0F0D0B" : "#F5EDD8"
          : gold ? "#C9A96E" : "rgba(245,237,216,0.65)",
        background: hov
          ? gold ? "#C9A96E" : "rgba(245,237,216,0.07)"
          : "transparent",
        fontFamily: "'Inter', sans-serif",
        fontSize: "0.65rem",
        fontWeight: 700,
        letterSpacing: "0.22em",
        textTransform: "uppercase",
        textDecoration: "none",
        transition: "all 0.25s ease",
        cursor: "pointer",
        whiteSpace: "nowrap",
      }}
    >
      {children}
    </a>
  );
};

/* ── Hero ──────────────────────────────────────────────────── */
const Hero: React.FC = () => {
  const { t } = useLanguage();
  const [currentSlide, setCurrentSlide] = useState(0);
  const [heroBackground, setHeroBackground] = useState<string>("");
  const { scrollY } = useScrollAnimation();

  useEffect(() => {
    (async () => {
      try {
        const res = await fetch("/api/hero-config.php");
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.data?.heroBackground)
            setHeroBackground(data.data.heroBackground);
        }
      } catch {}
    })();
  }, []);

  useEffect(() => {
    const duration = slides[currentSlide]?.type === "memorial" ? 9000 : 5000;
    const t = setTimeout(() => setCurrentSlide(p => (p + 1) % slides.length), duration);
    return () => clearTimeout(t);
  }, [currentSlide]);

  const eyebrow: React.CSSProperties = {
    fontFamily: "'Inter', sans-serif",
    fontSize: "0.62rem",
    fontWeight: 700,
    letterSpacing: "0.3em",
    textTransform: "uppercase",
    color: "#C9A96E",
    marginBottom: "1.5rem",
    display: "block",
  };
  const heading: React.CSSProperties = {
    fontFamily: "'Cormorant Garamond', Georgia, serif",
    fontWeight: 700,
    lineHeight: 0.92,
    color: "#F5EDD8",
    textWrap: "balance" as any,
    marginBottom: "1.25rem",
  };
  const sub: React.CSSProperties = {
    fontFamily: "'Cormorant Garamond', Georgia, serif",
    fontStyle: "italic",
    color: "#7A6E64",
    marginBottom: "2.75rem",
    display: "block",
  };

  return (
    <section
      id="hero"
      className="relative w-full h-screen flex items-center justify-center text-center overflow-hidden"
      style={{
        backgroundImage: heroBackground ? `url(${heroBackground})` : "none",
        backgroundSize: "cover",
        backgroundPosition: `center calc(50% + ${Math.min(scrollY * 0.2, 120)}px)`,
        backgroundColor: "#0F0D0B",
      }}
    >
      {/* Dark overlay */}
      <div
        className="absolute inset-0 z-0 transition-colors duration-700"
        style={{
          background: slides[currentSlide]?.type === "memorial"
            ? "rgba(6,4,2,0.84)"
            : "rgba(0,0,0,0.72)",
        }}
      />
      {/* Subtle scanline texture */}
      <div
        className="absolute inset-0 z-0 pointer-events-none"
        style={{
          backgroundImage:
            "repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(255,255,255,0.011) 3px,rgba(255,255,255,0.011) 4px)",
        }}
      />

      {/* ── SLIDES ── */}
      <div className="relative z-10 w-full h-full">
        {slides.map((slide, index) => (
          <div
            key={slide.id}
            className={`absolute inset-0 flex flex-col items-center justify-center px-6 transition-opacity duration-700 ${
              currentSlide === index ? "opacity-100 z-10" : "opacity-0 z-0"
            }`}
          >

            {/* MEMORIAL */}
            {slide.type === "memorial" && (
              <div className="flex flex-col items-center max-w-2xl">
                <div className="flex gap-5 mb-8">
                  {[["#DC2626","rgba(220,38,38,0.5)"],["#E2E8F0","transparent"],["#3B82F6","rgba(59,130,246,0.5)"]].map(([c, s], i) => (
                    <span key={i} style={{ color: c, fontSize: "2rem", textShadow: `0 2px 14px ${s}` }}>★</span>
                  ))}
                </div>
                <h1 style={{ ...heading, fontSize: "clamp(2.8rem,10vw,5.5rem)", textShadow: "0 4px 28px rgba(0,0,0,0.7)" }}>
                  {t("hero.holiday.title")}
                </h1>
                <p style={{ ...sub, fontSize: "clamp(0.95rem,2.8vw,1.3rem)", marginBottom: "1.5rem" }}>
                  {t("hero.holiday.subtitle")}
                </p>
                <div className="flex items-center gap-4 w-full max-w-xs mb-4">
                  <span className="gold-line flex-1" />
                  <span style={{ color: "#C9A96E", fontSize: "0.75rem" }}>★</span>
                  <span className="gold-line flex-1" />
                </div>
                {slide.hours && (
                  <p style={{ color: "#7A6E64", fontSize: "clamp(0.82rem,2vw,0.95rem)", letterSpacing: "0.08em" }}>
                    {t("hero.holiday.hours")}
                  </p>
                )}
              </div>
            )}

            {/* MAIN */}
            {slide.type === "main" && (
              <div className="max-w-3xl mx-auto">
                <span style={eyebrow}>Valdosta · Georgia · Since 2019</span>
                <h1 style={{ ...heading, fontSize: "clamp(2.8rem,9vw,6rem)" }}>
                  {t("hero.main.title")}
                </h1>
                <span style={{ ...sub, fontSize: "clamp(1rem,2.5vw,1.35rem)" }}>
                  {t("hero.main.subtitle")}
                </span>
                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                  <HeroBtn href={slide.buttonLink!} external>{t("hero.main.orderNow")}</HeroBtn>
                  <HeroBtn href="https://order.online/business/stone-pho-lp-14380597" external ghost>
                    {t("hero.main.orderDelivery")}
                  </HeroBtn>
                </div>
              </div>
            )}

            {/* REVIEWS */}
            {slide.type === "reviews" && (
              <div className="max-w-4xl mx-auto w-full">
                <span style={eyebrow}>Customer Reviews</span>
                <h2 style={{ ...heading, fontSize: "clamp(2rem,5vw,3.5rem)", marginBottom: "2rem" }}>
                  {t("hero.reviews.heading")}
                </h2>
                <div className="grid gap-4 grid-cols-1 sm:grid-cols-3 mb-8">
                  {slide.reviews?.map((r, idx) => (
                    <div
                      key={idx}
                      style={{
                        background: "rgba(255,255,255,0.04)",
                        backdropFilter: "blur(16px)",
                        border: "1px solid rgba(201,169,110,0.14)",
                        padding: "1.25rem",
                        textAlign: "left",
                      }}
                    >
                      <div className="flex mb-3 gap-0.5">
                        {Array.from({ length: 5 }).map((_, i) => (
                          <Star key={i} className="w-3.5 h-3.5 text-lux-gold fill-lux-gold" />
                        ))}
                      </div>
                      <p style={{ color: "#F5EDD8", fontSize: "0.8rem", lineHeight: 1.55, marginBottom: "0.75rem" }}>
                        "{r.text}"
                      </p>
                      <p style={{ color: "#4A4540", fontSize: "0.7rem" }}>— {r.name}, {r.date}</p>
                    </div>
                  ))}
                </div>
                <HeroBtn href="https://www.google.com/search?q=stone+pho+valdosta" external>
                  {t("hero.reviews.readMore")}
                </HeroBtn>
              </div>
            )}

            {/* SPECIAL */}
            {slide.type === "special" && (
              <div className="max-w-3xl mx-auto">
                <span style={eyebrow}>Chef's Signature</span>
                <h2 style={{ ...heading, fontSize: "clamp(2.5rem,7vw,5rem)" }}>
                  {t("hero.special.title")}
                </h2>
                <span style={{ ...sub, fontSize: "clamp(0.95rem,2.5vw,1.25rem)" }}>
                  {t("hero.special.subtitle")}
                </span>
                <HeroBtn href={slide.buttonLink!}>{t("hero.special.viewMenu")}</HeroBtn>
              </div>
            )}
          </div>
        ))}
      </div>

      {/* ── SLIDE INDICATORS — line style ── */}
      <div className="absolute bottom-10 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
        {slides.map((_, i) => (
          <button
            key={i}
            onClick={() => setCurrentSlide(i)}
            aria-label={`Slide ${i + 1}`}
            style={{
              width: i === currentSlide ? "2.5rem" : "0.6rem",
              height: "2px",
              background: i === currentSlide ? "#C9A96E" : "#3A3530",
              border: "none",
              padding: 0,
              cursor: "pointer",
              transition: "all 0.4s ease",
            }}
          />
        ))}
      </div>

      {/* Scroll hint */}
      <ChevronDown
        className="absolute bottom-9 right-8 z-20 animate-bounce"
        style={{ color: "#3A3530", width: "1.25rem", height: "1.25rem" }}
      />
    </section>
  );
};

export default Hero;
