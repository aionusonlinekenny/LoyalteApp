import React, { useEffect, useState } from "react";
import { Star } from "lucide-react";

interface Review {
  name: string;
  text: string;
  date: string;
}

interface Slide {
  id: string;
  type: "main" | "reviews" | "special";
  title?: string;
  subtitle?: string;
  buttonText?: string;
  buttonLink?: string;
  reviews?: Review[];
}

const slides: Slide[] = [
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
      {
        name: "Sarah M.",
        text: "Best pho I've had outside of Vietnam! The broth is so rich and flavorful.",
        date: "2 days ago",
      },
      {
        name: "James K.",
        text: "The spring rolls are amazing! So fresh and delicious.",
        date: "1 week ago",
      },
      {
        name: "Emily R.",
        text: "Finally, authentic Vietnamese food in Valdosta. Highly recommend!",
        date: "3 weeks ago",
      },
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

const Hero: React.FC = () => {
  const [currentSlide, setCurrentSlide] = useState(0);
  const [heroBackground, setHeroBackground] = useState<string>("");

  // Load hero background config
  useEffect(() => {
    const loadHeroConfig = async () => {
      try {
        const res = await fetch("/api/hero-config.php");
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.data.heroBackground) {
            setHeroBackground(data.data.heroBackground);
          }
        }
      } catch (err) {
        console.error("Failed to load hero background:", err);
      }
    };
    loadHeroConfig();
  }, []);

  // Auto slide
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 5000);
    return () => clearInterval(timer);
  }, []);

  return (
    <section
      id="hero"
      className="relative w-full h-screen flex items-center justify-center text-center text-white overflow-hidden"
      style={{
        backgroundImage: heroBackground ? `url(${heroBackground})` : "none",
        backgroundSize: "cover",
        backgroundPosition: "center",
      }}
    >
      {/* Overlay */}
      <div className="absolute inset-0 bg-white/30 z-0"></div>

      {/* Slider wrapper */}
      <div className="relative z-10 w-full h-full flex items-center justify-center">
        <div className="relative w-full h-full overflow-hidden">
          {slides.map((slide, index) => (
            <div
              key={slide.id}
              className={`absolute inset-0 flex flex-col items-center justify-center px-4 transition-opacity duration-700 ease-in-out ${
                currentSlide === index ? "opacity-100 z-10" : "opacity-0 z-0"
              }`}
            >
              {slide.type === "main" && (
                <>
                  <h1 className="text-4xl md:text-6xl font-bold mb-6 drop-shadow-lg">
                    {slide.title}
                  </h1>
                  <p className="text-lg md:text-2xl mb-8 drop-shadow-lg">
                    {slide.subtitle}
                  </p>
                  <div className="flex flex-col sm:flex-row gap-4 justify-center">
                    {slide.buttonText && (
                      <a
                        href={slide.buttonLink}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-full font-semibold text-lg transition-transform transform hover:scale-105"
                      >
                        {slide.buttonText}
                      </a>
                    )}
                    {/* ✅ Nút Delivery thêm vào slide đầu tiên */}
                    <a
                      href="https://order.online/business/stone-pho-lp-14380597"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-full font-semibold text-lg transition-transform transform hover:scale-105"
                    >
                      Order Delivery
                    </a>
                  </div>
                </>
              )}

              {slide.type === "reviews" && (
                <div className="max-w-4xl mx-auto">
                  <h2 className="text-3xl md:text-4xl font-bold mb-8">
                    What Our Customers Say
                  </h2>
                  <div className="grid gap-4 mb-8 max-w-4xl mx-auto grid-cols-1 sm:grid-cols-2 md:grid-cols-3">
                    {slide.reviews?.map((review, idx) => (
                      <div
                        key={idx}
                        className="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-left"
                      >
                        <div className="flex items-center mb-2">
                          {Array.from({ length: 5 }).map((_, i) => (
                            <Star
                              key={i}
                              className="w-4 h-4 text-yellow-400 fill-yellow-400"
                            />
                          ))}
                        </div>
                        <p className="text-sm mb-2">"{review.text}"</p>
                        <p className="text-xs text-gray-300">
                          — {review.name}, {review.date}
                        </p>
                      </div>
                    ))}
                  </div>
                  <a
                    href="https://www.google.com/search?q=stone+pho+valdosta"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-full font-semibold text-lg transition-transform transform hover:scale-105"
                  >
                    Read More Reviews
                  </a>
                </div>
              )}

              {slide.type === "special" && (
                <>
                  <h2 className="text-3xl md:text-5xl font-bold mb-6 drop-shadow-lg">
                    {slide.title}
                  </h2>
                  <p className="text-lg md:text-2xl mb-8 drop-shadow-lg">
                    {slide.subtitle}
                  </p>
                  {slide.buttonText && (
                    <a
                      href={slide.buttonLink}
                      className="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-full font-semibold text-lg transition-transform transform hover:scale-105"
                    >
                      {slide.buttonText}
                    </a>
                  )}
                </>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Dots */}
      <div className="absolute bottom-8 left-0 right-0 flex justify-center space-x-2 z-20">
        {slides.map((_, index) => (
          <button
            key={index}
            onClick={() => setCurrentSlide(index)}
            className={`w-3 h-3 rounded-full transition-colors ${
              currentSlide === index ? "bg-white" : "bg-gray-400"
            }`}
          ></button>
        ))}
      </div>
    </section>
  );
};

export default Hero;
