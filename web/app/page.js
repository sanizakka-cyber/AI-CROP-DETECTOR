'use client';

import { useEffect, useState } from 'react';
import Image from 'next/image';
import {
  ArrowRight,
  BarChart3,
  Beef,
  Bell,
  BookOpen,
  Check,
  ChevronDown,
  ChevronRight,
  ClipboardList,
  DollarSign,
  Facebook,
  HeartPulse,
  Instagram,
  Leaf,
  Linkedin,
  Lock,
  Mail,
  MapPin,
  Menu,
  MessageCircle,
  Moon,
  Phone,
  Play,
  Sprout,
  Star,
  Stethoscope,
  Sun,
  User,
  UserPlus,
  Wheat,
  X,
  AlertCircle,
} from 'lucide-react';

const navItems = [
  ['Home', 'home'],
  ['About Us', 'about-us'],
  ['Services', 'services'],
  ['Marketplace', 'marketplace'],
  ['Resources', 'resources'],
  ['Contact Us', 'contact-us'],
  ['Feedback', 'feedback'],
  ['FAQ', 'faq'],
];

const stats = [
  ['500+', 'Happy Farmers'],
  ['2,000+', 'Livestock Managed'],
  ['50+', 'Partner Vets'],
  ['24/7', 'Support'],
];

const services = [
  { 
    id: 'livestock',
    title: 'Livestock Management', 
    desc: 'Record animals, track health, breeding, weight, and more.',
    image: '/placeholder-livestock.jpg',
    alt: 'Farmer managing cattle and goats on farm'
  },
  { 
    id: 'poultry',
    title: 'Poultry Management', 
    desc: 'Manage poultry, egg production, feed, sales and performance.',
    image: '/placeholder-poultry.jpg',
    alt: 'Poultry farm with chickens in organized housing'
  },
  { 
    id: 'crops',
    title: 'Crop Farming', 
    desc: 'Plan, monitor and record your crops for better yield.',
    image: '/placeholder-crops.jpg',
    alt: 'Lush green crop field with maize and vegetables'
  },
  { 
    id: 'veterinary',
    title: 'Veterinary Services', 
    desc: 'Consult with experts, check symptoms and get advice.',
    image: '/placeholder-vet.jpg',
    alt: 'Veterinary doctor examining and treating livestock'
  },
  { 
    id: 'marketplace',
    title: 'Marketplace', 
    desc: 'Buy and sell livestock, farm inputs and agricultural products.',
    image: '/placeholder-market.jpg',
    alt: 'Farm inputs, feed bags, and agricultural products on display'
  },
  { 
    id: 'finance',
    title: 'Finance Tools', 
    desc: 'Track income, expenses and profits easily.',
    image: '/placeholder-finance.jpg',
    alt: 'Farmer using mobile phone and calculator for record keeping'
  },
];

const actionCards = [
  { id: 'poultry-farm', label: 'Poultry Farm', image: '/placeholder-gallery-poultry.jpg', alt: 'Real poultry farm operation with organized bird housing' },
  { id: 'goats-rams', label: 'Goats & Rams', image: '/placeholder-gallery-goats.jpg', alt: 'Healthy goats and rams grazing on farm' },
  { id: 'green-crops', label: 'Green Crops', image: '/placeholder-gallery-crops.jpg', alt: 'Lush green and healthy crop field' },
  { id: 'vet-services', label: 'Vet Services', image: '/placeholder-gallery-vet.jpg', alt: 'Veterinary professional providing care to livestock' },
  { id: 'happy-farmers', label: 'Happy Farmers', image: '/placeholder-gallery-farmers.jpg', alt: 'Smiling Northern Nigerian farmers using MSAS platform' },
  { id: 'dashboard', label: 'Dashboard', image: '/placeholder-gallery-dashboard.jpg', alt: 'Screenshot of MSAS mobile and web dashboard interface' },
];

const testimonials = [
  [
    'MSAS platform has improved how I manage my poultry farm. The health alerts and expert advice are just amazing!',
    'Amina Hassan',
    'Poultry Farmer, Katsina',
  ],
  [
    'I can now record my animals, track expenses and even sell my goats online. Very helpful platform!',
    'Kabiru Usman',
    'Livestock Farmer, Kano',
  ],
  [
    'The veterinary support is fast and reliable. I recommend MSAS to every farmer.',
    'Fatima Bello',
    'Crop Farmer, Katsina',
  ],
];

const quickStats = [
  [Beef, 'Cattle', '120'],
  [Leaf, 'Poultry', '350'],
  [Wheat, 'Crops', '5'],
  [Bell, 'Alerts', '3'],
];

const quickAccess = [
  [HeartPulse, 'Health Check'],
  [Phone, 'Vet Consultation'],
  [BarChart3, 'Marketplace'],
  [ClipboardList, 'Record Keeping'],
];

function Logo() {
  return (
    <a href="#home" className="logo" aria-label="MSAS home">
      <span className="logo-mark">
        <Beef size={22} aria-hidden="true" />
      </span>
      <span className="logo-copy">
        <strong>MSAS</strong>
        <span>Livestock & Agro Services</span>
        <small>Smart Agriculture · Healthy Livestock · Better Future</small>
      </span>
    </a>
  );
}

function Header() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [darkMode, setDarkMode] = useState(true);
  const [active, setActive] = useState('home');

  useEffect(() => {
    document.documentElement.classList.toggle('light-mode', !darkMode);
  }, [darkMode]);

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
        if (visible) setActive(visible.target.id);
      },
      { rootMargin: '-35% 0px -55% 0px', threshold: [0.1, 0.25, 0.5] },
    );

    navItems.forEach(([, id]) => {
      const node = document.getElementById(id);
      if (node) observer.observe(node);
    });

    return () => observer.disconnect();
  }, []);

  return (
    <header className="site-header">
      <div className="top-banner">
        <div className="marquee" aria-label="Announcement">
          <span>
            <Sprout size={16} /> Welcome to MSAS Livestock & Agro Services
          </span>
          <span>Empowering Farmers. Improving Livestock. Building a Better Future.</span>
        </div>
        <label className="language-select">
          <span className="sr-only">Language</span>
          <select defaultValue="English" aria-label="Select language">
            <option>English</option>
            <option>Hausa</option>
            <option>Yoruba</option>
            <option>Igbo</option>
          </select>
          <ChevronDown size={14} aria-hidden="true" />
        </label>
      </div>

      <nav className="navbar" aria-label="Primary navigation">
        <Logo />

        <div className="desktop-nav">
          {navItems.map(([label, id]) => (
            <a key={id} className={active === id ? 'active' : ''} href={`#${id}`}>
              {label}
            </a>
          ))}
        </div>

        <div className="nav-actions">
          <button
            type="button"
            className="icon-button"
            onClick={() => setDarkMode((value) => !value)}
            aria-label={darkMode ? 'Switch to light mode' : 'Switch to dark mode'}
          >
            {darkMode ? <Sun size={18} /> : <Moon size={18} />}
          </button>
          <a className="btn btn-outline compact" href="#signin">
            Sign In
          </a>
          <a className="btn btn-primary compact" href="#signup">
            Sign Up
          </a>
          <a className="btn btn-amber compact" href="#check-in">
            Check In
          </a>
          <button
            type="button"
            className="icon-button menu-button"
            onClick={() => setMenuOpen((value) => !value)}
            aria-label="Toggle menu"
          >
            {menuOpen ? <X size={22} /> : <Menu size={22} />}
          </button>
        </div>
      </nav>

      {menuOpen && (
        <div className="mobile-nav">
          {navItems.map(([label, id]) => (
            <a key={id} href={`#${id}`} onClick={() => setMenuOpen(false)}>
              {label}
            </a>
          ))}
          <div className="mobile-actions">
            <a className="btn btn-outline" href="#signin">Sign In</a>
            <a className="btn btn-primary" href="#signup">Sign Up</a>
            <a className="btn btn-amber" href="#check-in">Check In</a>
          </div>
        </div>
      )}
    </header>
  );
}

function PhoneMockup() {
  return (
    <div className="phone-shell" aria-label="MSAS mobile dashboard preview">
      <div className="phone-notch" />
      <div className="phone-screen">
        <div className="phone-top">
          <strong>Dashboard</strong>
          <span>Welcome back, Farmer</span>
        </div>
        <div className="phone-body">
          <div className="phone-grid">
            {quickStats.map(([Icon, label, value]) => (
              <div className="mini-stat" key={label}>
                <Icon size={21} />
                <span>{label}</span>
                <strong>{value}</strong>
              </div>
            ))}
          </div>
          <strong className="quick-title">Quick Access</strong>
          <div className="quick-list">
            {quickAccess.map(([Icon, label]) => (
              <div className="quick-row" key={label}>
                <Icon size={16} />
                <span>{label}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function Hero() {
  return (
    <section id="home" className="hero section-band">
      <div className="grid-pattern" aria-hidden="true" />
      <div className="container hero-grid">
        <div className="hero-copy reveal">
          <h1>
            <span>Welcome to</span>
            <strong>MSAS Livestock & Agro Services</strong>
          </h1>
          <p>
            A smart digital platform for livestock farmers, poultry owners, and agribusiness
            operators. Manage your animals, access expert veterinary support, buy and sell inputs,
            and grow your farm business with confidence.
          </p>
          <div className="cta-row">
            <a className="btn btn-primary large" href="#signin">
              <User size={19} /> Sign In
            </a>
            <a className="btn btn-amber large" href="#signup">
              <UserPlus size={19} /> Sign Up
            </a>
            <a className="btn btn-ghost large" href="#check-in">
              <Check size={19} /> Check In
            </a>
            <a className="btn btn-white large" href="#about-us">
              Learn More <ArrowRight size={18} />
            </a>
          </div>
          <div className="feature-badges" aria-label="Platform features">
            {[
              [Phone, 'Smart Management'],
              [Stethoscope, 'Expert Support'],
              [BarChart3, 'AI Powered'],
              [Lock, 'Secure & Reliable'],
            ].map(([Icon, label]) => (
              <div className="feature-badge" key={label}>
                <Icon size={22} />
                <span>{label}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="hero-visual reveal">
          <PhoneMockup />
          <div className="farm-hand-card">
            <span className="card-icon"><Leaf size={28} /></span>
            <h2>Your Farm, In Your Hand</h2>
            <p>Manage, monitor and grow your farm business anytime, anywhere.</p>
            <ul>
              {['Health Alerts', 'Record Keeping', 'Vet Consultation', 'Buy & Sell'].map((item) => (
                <li key={item}><Check size={16} /> {item}</li>
              ))}
            </ul>
            <div className="split-actions">
              <a className="btn btn-whatsapp" href="https://wa.me/2348129582957">
                <MessageCircle size={16} /> WhatsApp
              </a>
              <a className="btn btn-primary" href="#signup">Get Started Today</a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function About() {
  return (
    <section id="about-us" className="section-band alt">
      <div className="container about-grid">
        <article className="info-panel reveal">
          <h2>About Our CEO</h2>
          <div className="ceo-profile">
            <div className="ceo-photo" aria-hidden="true">
              <User size={74} />
            </div>
            <div>
              <h3>Sani Yawale Zakka</h3>
              <strong>Founder & Chief Executive Officer</strong>
              <p>
                Sani Yawale Zakka is a visionary entrepreneur passionate about agriculture,
                livestock development and digital innovation. He founded MSAS Livestock & Agro
                Services to transform traditional farming into a profitable, efficient and
                technology-driven industry that benefits farmers, communities and the wider economy.
              </p>
              <div className="contact-lines">
                <a href="tel:08032459879"><Phone size={16} /> 08032459879</a>
                <a href="mailto:sanizakka@gmail.com"><Mail size={16} /> sanizakka@gmail.com</a>
              </div>
            </div>
          </div>
        </article>

        <article className="info-panel reveal">
          <h2>About MSAS</h2>
          <p>
            MSAS Livestock & Agro Services was created to solve the challenges farmers face in
            livestock management, poor market access, low productivity and lack of expert support.
            We combine agriculture with technology to make farming easier, smarter and more
            profitable.
          </p>
          <div className="stats-grid">
            {stats.map(([number, label]) => (
              <div className="stat-card" key={label}>
                <strong>{number}</strong>
                <span>{label}</span>
              </div>
            ))}
          </div>
        </article>
      </div>
    </section>
  );
}

function Services() {
  return (
    <section id="services" className="section-band">
      <div className="container">
        <div className="section-heading reveal">
          <h2>Our Services</h2>
          <p>Everything you need to run a successful farm business</p>
        </div>
        <div className="services-grid">
          {services.map((service) => (
            <article className="service-card reveal" key={service.id}>
              <div className="service-image-container">
                <Image
                  src={service.image}
                  alt={service.alt}
                  width={400}
                  height={192}
                  className="service-image"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    const parent = e.target.parentElement;
                    parent.innerHTML = `
                      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: linear-gradient(to bottom right, rgb(226, 232, 240), rgb(203, 213, 225));">
                        <span style="font-size: 2rem; margin-bottom: 0.5rem;">📸</span>
                        <span style="font-size: 0.75rem; color: rgb(71, 85, 105); text-align: center; padding: 0 0.5rem;">${service.alt}</span>
                      </div>
                    `;
                  }}
                />
              </div>
              <h3>{service.title}</h3>
              <p>{service.desc}</p>
              <a href="#contact-us" className="learn-more-link">Learn More <ArrowRight size={16} /></a>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

function ActionGallery() {
  return (
    <section id="marketplace" className="section-band alt">
      <div className="container">
        <div className="section-heading reveal">
          <h2>Agriculture In Action</h2>
          <p>Real farms. Real people. Real impact.</p>
        </div>
        <div className="action-gallery">
          {actionCards.map((card) => (
            <article className="action-card reveal" key={card.id}>
              <div className="gallery-image-wrapper">
                <Image
                  src={card.image}
                  alt={card.alt}
                  width={200}
                  height={200}
                  className="gallery-image"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    const parent = e.target.parentElement;
                    parent.innerHTML = `
                      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: linear-gradient(to bottom right, rgb(167, 243, 208), rgb(134, 239, 172));">
                        <span style="font-size: 1.75rem; margin-bottom: 0.25rem;">📸</span>
                        <span style="font-size: 0.625rem; font-weight: 600; color: rgb(20, 83, 45); text-align: center; padding: 0 0.25rem;">${card.alt}</span>
                      </div>
                    `;
                  }}
                />
              </div>
              <strong>{card.label}</strong>
            </article>
          ))}
        </div>
        <div className="gallery-note">
          <AlertCircle size={18} />
          <span>Gallery images use placeholders. Replace with real photography from your farms for authentic representation.</span>
        </div>
      </div>
    </section>
  );
}

function Testimonials() {
  const [feedback, setFeedback] = useState('');

  return (
    <section id="feedback" className="section-band">
      <div className="container">
        <div className="section-heading reveal">
          <h2>What Our Farmers Say</h2>
        </div>
        <div className="testimonial-layout">
          <div className="testimonial-grid">
            {testimonials.map(([quote, name, role]) => (
              <article className="testimonial-card reveal" key={name}>
                <div className="stars" aria-label="5 star rating">
                  {Array.from({ length: 5 }).map((_, index) => (
                    <Star key={index} size={17} fill="currentColor" />
                  ))}
                </div>
                <p>&ldquo;{quote}&rdquo;</p>
                <div className="person">
                  <span><User size={20} /></span>
                  <div>
                    <strong>{name}</strong>
                    <small>{role}</small>
                  </div>
                </div>
              </article>
            ))}
          </div>

          <form className="feedback-card reveal" onSubmit={(event) => event.preventDefault()}>
            <h3>Share Your Feedback</h3>
            <p>Help us serve you better</p>
            <label>
              <span className="sr-only">Feedback</span>
              <textarea
                value={feedback}
                onChange={(event) => setFeedback(event.target.value)}
                placeholder="Write your feedback..."
                rows={5}
              />
            </label>
            <button type="submit">Give Feedback</button>
          </form>
        </div>
      </div>
    </section>
  );
}

function ResourcesFaq() {
  return (
    <section id="resources" className="section-band alt resources-section">
      <div className="container resource-grid">
        <article className="info-panel reveal">
          <h2>Resources</h2>
          <p>
            Practical guides, farmer training, market notes and veterinary education are built into
            the MSAS platform so farm decisions can be made with clearer information.
          </p>
          <div className="resource-list">
            {['Training & Resources', 'Partner Vets', 'Market Updates'].map((item) => (
              <a href="#contact-us" key={item}><BookOpen size={17} /> {item}</a>
            ))}
          </div>
        </article>
        <article id="faq" className="info-panel reveal">
          <h2>FAQ</h2>
          <details open>
            <summary>Who can use MSAS?</summary>
            <p>Livestock farmers, poultry owners, crop farmers and agribusiness operators.</p>
          </details>
          <details>
            <summary>Can I contact a vet?</summary>
            <p>Yes. MSAS supports expert consultation and fast response channels.</p>
          </details>
          <details>
            <summary>Does MSAS support marketplace activity?</summary>
            <p>Yes. Farmers can buy and sell livestock, inputs and agricultural products.</p>
          </details>
        </article>
      </div>
    </section>
  );
}

function Contact() {
  return (
    <section id="contact-us" className="section-band contact-section">
      <div className="container contact-grid">
        <article className="contact-copy reveal">
          <h2>Contact Us</h2>
          <p>We are always here to help you grow</p>
          <div className="contact-lines large">
            <a href="mailto:msaslivestockagroservices@gmail.com">
              <Mail size={18} /> msaslivestockagroservices@gmail.com
            </a>
            <a href="tel:08129582957"><Phone size={18} /> 08129582957</a>
            <span><MapPin size={18} /> No 21 Sarkin maska street dutsin safe lowcost Katsina State, Nigeria</span>
          </div>
        </article>

        <article className="contact-card reveal">
          <h3>Get In Touch</h3>
          <p>We respond within minutes</p>
          <div className="split-actions">
            <a className="btn btn-ghost" href="tel:08129582957"><Phone size={16} /> Call Now</a>
            <a className="btn btn-whatsapp" href="https://wa.me/2348129582957">WhatsApp Us</a>
          </div>
        </article>

        <article className="contact-card reveal">
          <MapPin size={42} />
          <h3>Visit Our Office</h3>
          <p>MSAS Livestock & Agro Services, Katsina State, Nigeria</p>
          <a className="btn btn-outline" href="https://maps.google.com/?q=Katsina+State+Nigeria">
            View on Map <ArrowRight size={16} />
          </a>
        </article>
      </div>
    </section>
  );
}

function Footer() {
  const quickLinks = ['Home', 'About Us', 'Services', 'FAQ', 'Marketplace', 'Contact Us'];
  const footerServices = [
    'Livestock Management',
    'Poultry Management',
    'Crop Farming',
    'Finance Tools',
    'Training & Resources',
    'Partner Vets',
  ];
  const social = [
    [Facebook, 'Facebook'],
    [Play, 'YouTube'],
    [Instagram, 'Instagram'],
    [Linkedin, 'LinkedIn'],
    [MessageCircle, 'WhatsApp'],
  ];

  return (
    <footer className="footer">
      <div className="container footer-grid">
        <div>
          <Logo />
          <p>Smart Agriculture · Healthy Livestock · Better Future</p>
          <div className="social-row">
            {social.map(([Icon, label]) => (
              <a key={label} href="#" aria-label={label}><Icon size={18} /></a>
            ))}
          </div>
        </div>
        <div>
          <h3>Quick Links</h3>
          {quickLinks.map((item) => (
            <a key={item} href={`#${item.toLowerCase().replaceAll(' ', '-')}`}>{item}</a>
          ))}
        </div>
        <div>
          <h3>Services</h3>
          {footerServices.map((item) => <a key={item} href="#services">{item}</a>)}
        </div>
        <div>
          <h3>Follow Us</h3>
          <div className="follow-stack">
            {social.map(([Icon, label]) => (
              <a key={label} href="#"><Icon size={17} /> {label}</a>
            ))}
          </div>
        </div>
      </div>
      <div className="footer-bottom">
        <span>© 2024 MSAS Livestock & Agro Services. All rights reserved.</span>
        <div>
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Cookie Policy</a>
        </div>
      </div>
    </footer>
  );
}

export default function Home() {
  return (
    <>
      <Header />
      <main>
        <Hero />
        <About />
        <Services />
        <ActionGallery />
        <Testimonials />
        <ResourcesFaq />
        <Contact />
      </main>
      <Footer />
      <a className="floating-whatsapp" href="https://wa.me/2348129582957" aria-label="Chat on WhatsApp">
        <MessageCircle size={22} />
        <span>Chat on WhatsApp</span>
      </a>
    </>
  );
}
