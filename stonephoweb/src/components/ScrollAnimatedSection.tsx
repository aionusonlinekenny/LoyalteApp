import React from 'react';
import { useIntersectionObserver } from '../hooks/useScrollAnimation';

interface ScrollAnimatedSectionProps {
  children: React.ReactNode;
  className?: string;
  animation?: 'fadeInUp' | 'fadeInLeft' | 'fadeInRight' | 'fadeIn' | 'scaleIn' | 'slideInUp';
  delay?: number;
  duration?: number;
}

const ScrollAnimatedSection: React.FC<ScrollAnimatedSectionProps> = ({
  children,
  className = '',
  animation = 'fadeInUp',
  delay = 0,
  duration = 800,
}) => {
  const { ref, hasAnimated } = useIntersectionObserver();

  const getAnimationClasses = () => {
    const baseClasses = 'transition-all ease-out';
    const durationClass = `duration-${duration}`;
    
    if (!hasAnimated) {
      switch (animation) {
        case 'fadeInUp':
          return `${baseClasses} ${durationClass} opacity-0 translate-y-8`;
        case 'fadeInLeft':
          return `${baseClasses} ${durationClass} opacity-0 -translate-x-8`;
        case 'fadeInRight':
          return `${baseClasses} ${durationClass} opacity-0 translate-x-8`;
        case 'fadeIn':
          return `${baseClasses} ${durationClass} opacity-0`;
        case 'scaleIn':
          return `${baseClasses} ${durationClass} opacity-0 scale-95`;
        case 'slideInUp':
          return `${baseClasses} ${durationClass} opacity-0 translate-y-12`;
        default:
          return `${baseClasses} ${durationClass} opacity-0 translate-y-8`;
      }
    }

    return `${baseClasses} ${durationClass} opacity-100 translate-y-0 translate-x-0 scale-100`;
  };

  return (
    <div
      ref={ref}
      className={`${getAnimationClasses()} ${className}`}
      style={{
        transitionDelay: `${delay}ms`,
        transitionDuration: `${duration}ms`,
      }}
    >
      {children}
    </div>
  );
};

export default ScrollAnimatedSection;