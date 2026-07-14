// CEO, About, Stats, Services, Gallery, Testimonials, Contact, Footer — all in one sections file
'use client';
import { useState } from 'react';
import { Phone, Mail, MapPin, Star, ChevronRight, AlertCircle } from 'lucide-react';
import Image from 'next/image';

const SERVICES = [
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
    desc: 'Buy & sell livestock, farm inputs and agricultural products.',
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

const GALLERY = [
  { 
    id: 'poultry-farm',
    label: 'Poultry Farm',
    image: '/placeholder-gallery-poultry.jpg',
    alt: 'Real poultry farm operation with organized bird housing'
  },
  { 
    id: 'goats-rams',
    label: 'Goats & Rams',
    image: '/placeholder-gallery-goats.jpg',
    alt: 'Healthy goats and rams grazing on farm'
  },
  { 
    id: 'green-crops',
    label: 'Green Crops',
    image: '/placeholder-gallery-crops.jpg',
    alt: 'Lush green and healthy crop field'
  },
  { 
    id: 'vet-services',
    label: 'Vet Services',
    image: '/placeholder-gallery-vet.jpg',
    alt: 'Veterinary professional providing care to livestock'
  },
  { 
    id: 'happy-farmers',
    label: 'Happy Farmers',
    image: '/placeholder-gallery-farmers.jpg',
    alt: 'Smiling Northern Nigerian farmers using MSAS platform'
  },
  { 
    id: 'dashboard',
    label: 'Dashboard',
    image: '/placeholder-gallery-dashboard.jpg',
    alt: 'Screenshot of MSAS mobile and web dashboard interface'
  },
];

const TESTIMONIALS = [
  { name: 'Amina Hassan', role: 'Poultry Farmer, Katsina', text: 'MSAS platform has improved how I manage my poultry farm. The health alerts and expert advice are just amazing!', stars: 5 },
  { name: 'Kabiru Usman', role: 'Livestock Farmer, Kano', text: 'I can now record my animals, track expenses and even sell my goats online. Very helpful platform!', stars: 5 },
  { name: 'Fatima Bello', role: 'Crop Farmer, Katsina', text: 'The veterinary support is fast and reliable. I recommend MSAS to every farmer.', stars: 5 },
];

export function About() {
  const [activeFeature, setActiveFeature] = useState(null);

  const features = [
    {
      id: 'smart-mgmt',
      title: 'Smart Management',
      icon: '📋',
      description: 'Comprehensive farm management and record-keeping features that help you organize and track all aspects of your agricultural operations efficiently.'
    },
    {
      id: 'expert-support',
      title: 'Expert Support',
      icon: '👨‍⚕️',
      description: 'Connect with certified veterinarians and agronomists. Book consultations, get expert advice, and receive guidance specific to your farm needs.'
    },
    {
      id: 'ai-powered',
      title: 'AI Powered',
      icon: '🤖',
      description: 'Advanced AI diagnostic scanning technology that identifies crop diseases, livestock health issues, and provides actionable treatment recommendations.'
    },
    {
      id: 'secure-reliable',
      title: 'Secure & Reliable',
      icon: '🔒',
      description: 'Enterprise-grade security, NDPR compliance, encrypted data storage, and 24/7 uptime guarantee for your critical farm data.'
    }
  ];

  return (
    <section id="about-us" className="py-16 bg-white dark:bg-slate-900">
      <div className="max-w-7xl mx-auto px-4">
        {/* CEO & Company Profile */}
        <div className="grid md:grid-cols-2 gap-12 items-start mb-16">
          {/* CEO Block */}
          <div className="bg-gradient-to-br from-emerald-50 to-white dark:from-slate-800 dark:to-slate-900 rounded-3xl p-8 border border-emerald-200 dark:border-emerald-900">
            <h2 className="text-2xl font-bold text-slate-800 dark:text-white mb-6 border-b-2 border-emerald-600 pb-3">About Our CEO</h2>
            
            {/* CEO Photo */}
            <div className="mb-6 flex justify-center">
              <div className="relative w-40 h-40 rounded-full overflow-hidden border-4 border-emerald-600 shadow-lg flex-shrink-0 bg-emerald-100">
                <Image
                  src="/ceo-sani-yawale.jpg"
                  alt="Sani Yawale Zakka - CEO and Founder of MSAS"
                  width={160}
                  height={160}
                  className="w-full h-full object-cover"
                  priority
                />
              </div>
            </div>

            {/* CEO Info */}
            <div>
              <h3 className="text-xl font-bold text-slate-800 dark:text-white mb-1">Sani Yawale Zakka</h3>
              <p className="text-emerald-700 dark:text-emerald-400 font-semibold text-sm mb-4 bg-emerald-100 dark:bg-emerald-900 px-3 py-1 rounded-full inline-block">
                Founder &amp; Chief Executive Officer
              </p>
              <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-6">
                Sani Yawale Zakka is a visionary entrepreneur passionate about agriculture, livestock development and digital innovation. He founded MSAS Livestock &amp; Agro Services to transform traditional farming into a profitable, efficient and technology-driven industry that benefits farmers, communities and the wider economy.
              </p>
              <div className="space-y-2.5 bg-white dark:bg-slate-900 p-4 rounded-xl">
                <div className="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                  <Phone size={16} className="text-emerald-600 flex-shrink-0" />
                  <a href="tel:08032459879" className="hover:text-emerald-600 transition">08032459879</a>
                </div>
                <div className="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                  <Mail size={16} className="text-emerald-600 flex-shrink-0" />
                  <a href="mailto:sanizakka@gmail.com" className="hover:text-emerald-600 transition">sanizakka@gmail.com</a>
                </div>
              </div>
            </div>
          </div>

          {/* About MSAS Block */}
          <div className="bg-gradient-to-br from-emerald-50 to-white dark:from-slate-800 dark:to-slate-900 rounded-3xl p-8 border border-emerald-200 dark:border-emerald-900">
            <h2 className="text-2xl font-bold text-slate-800 dark:text-white mb-6 border-b-2 border-emerald-600 pb-3">About MSAS</h2>
            <p className="text-slate-600 dark:text-slate-400 leading-relaxed mb-6">
              <strong>MSAS Livestock &amp; Agro Services</strong> was created to solve the challenges farmers face in livestock management, poor market access, low productivity and lack of expert support. We combine agriculture with technology to make farming easier, smarter and more profitable.
            </p>
            <p className="text-slate-600 dark:text-slate-400 leading-relaxed mb-8 text-sm">
              <strong>Our Mission:</strong> Empower smallholder farmers with digital tools, expert guidance, and market access to build sustainable, profitable agricultural enterprises.
            </p>
            <div className="grid grid-cols-2 gap-4">
              {[['500+', 'Happy Farmers'],['2,000+', 'Livestock Managed'],['50+', 'Partner Vets'],['24/7', 'Support']].map(([value, label]) => (
                <div key={label} className="text-center p-4 bg-emerald-100 dark:bg-emerald-900 rounded-xl border border-emerald-300 dark:border-emerald-700">
                  <div className="text-2xl font-extrabold text-emerald-700 dark:text-emerald-300">{value}</div>
                  <div className="text-xs text-slate-600 dark:text-slate-300 mt-1 font-semibold">{label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Feature Badges - Now Functional */}
        <div className="mb-8">
          <h3 className="text-2xl font-bold text-slate-800 dark:text-white mb-8 text-center">Why Choose MSAS?</h3>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            {features.map(feature => (
              <button
                key={feature.id}
                onClick={() => setActiveFeature(activeFeature === feature.id ? null : feature.id)}
                className={`p-6 rounded-2xl border-2 transition-all text-left transform hover:scale-105 ${
                  activeFeature === feature.id
                    ? 'bg-emerald-700 text-white border-emerald-600'
                    : 'bg-white dark:bg-slate-800 border-emerald-200 dark:border-emerald-900 text-slate-800 dark:text-white hover:border-emerald-400'
                }`}
                aria-expanded={activeFeature === feature.id}
                aria-label={`Learn more about ${feature.title}`}
              >
                <div className="text-3xl mb-3">{feature.icon}</div>
                <h4 className="font-bold mb-2">{feature.title}</h4>
                <div className={`text-sm transition-all ${activeFeature === feature.id ? 'block' : 'hidden'}`}>
                  {feature.description}
                </div>
                <ChevronRight className={`mt-3 transition-transform ${activeFeature === feature.id ? 'rotate-90' : ''}`} size={20} />
              </button>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

export function Services() {
  return (
    <section id="services" className="py-16 bg-slate-50 dark:bg-slate-800">
      <div className="max-w-7xl mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold text-slate-800 dark:text-white">Our Services</h2>
          <p className="text-slate-500 dark:text-slate-400 mt-2">Everything you need to run a successful farm business</p>
        </div>
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {SERVICES.map(s => (
            <div key={s.id} className="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-xl hover:border-emerald-300 transition group">
              {/* Image Placeholder */}
              <div className="relative w-full h-48 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center overflow-hidden">
                <Image
                  src={s.image}
                  alt={s.alt}
                  width={400}
                  height={192}
                  className="w-full h-full object-cover"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.parentElement.innerHTML = `
                      <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800">
                        <div className="text-4xl mb-2">📸</div>
                        <div className="text-xs text-slate-600">Image: ${s.alt}</div>
                      </div>
                    `;
                  }}
                />
                <noscript>
                  <div className="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300">
                    <div className="text-4xl mb-2">📸</div>
                    <div className="text-xs text-center text-slate-600 px-2">{s.alt}</div>
                  </div>
                </noscript>
              </div>
              
              {/* Content */}
              <div className="p-6">
                <h3 className="font-bold text-slate-800 dark:text-white mb-2">{s.title}</h3>
                <p className="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">{s.desc}</p>
                <button className="mt-4 text-emerald-700 dark:text-emerald-400 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                  Learn More <ChevronRight size={16} />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export function Gallery() {
  return (
    <section className="py-16 bg-white dark:bg-slate-900">
      <div className="max-w-7xl mx-auto px-4">
        <div className="text-center mb-10">
          <h2 className="text-3xl font-bold text-slate-800 dark:text-white">Agriculture In Action</h2>
          <p className="text-slate-500 dark:text-slate-400 mt-2">Real farms. Real people. Real impact.</p>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {GALLERY.map((item) => (
            <div
              key={item.id}
              className="relative aspect-square bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-slate-700 dark:to-slate-800 rounded-2xl overflow-hidden hover:scale-105 transition cursor-pointer shadow-sm group"
            >
              <Image
                src={item.image}
                alt={item.alt}
                width={200}
                height={200}
                className="w-full h-full object-cover"
                onError={(e) => {
                  e.target.style.display = 'none';
                  const parent = e.target.parentElement;
                  parent.innerHTML = `
                    <div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-slate-700 dark:to-slate-800">
                      <span class="text-3xl">📸</span>
                      <span class="text-[10px] font-semibold text-emerald-800 dark:text-emerald-300 mt-1 px-1 text-center">${item.alt}</span>
                    </div>
                  `;
                }}
              />
              <noscript>
                <div className="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-emerald-100 to-emerald-200">
                  <span className="text-3xl">📸</span>
                  <span className="text-[10px] font-semibold text-emerald-800 mt-1">{item.alt}</span>
                </div>
              </noscript>
              
              {/* Label Overlay */}
              <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3">
                <p className="text-white text-xs font-bold text-center">{item.label}</p>
              </div>
            </div>
          ))}
        </div>
        
        {/* Image Source Info */}
        <div className="mt-10 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl flex gap-3">
          <AlertCircle size={20} className="text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
          <div className="text-sm text-blue-800 dark:text-blue-300">
            <strong>Note:</strong> Gallery images use placeholders. Replace with real photography from your farms for authentic representation.
          </div>
        </div>
      </div>
    </section>
  );
}

export function Testimonials() {
  const [feedback, setFeedback] = useState('');
  return (
    <section className="py-16 bg-slate-50 dark:bg-slate-800">
      <div className="max-w-7xl mx-auto px-4">
        <h2 className="text-3xl font-bold text-slate-800 dark:text-white text-center mb-12">What Our Farmers Say</h2>
        <div className="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
          {TESTIMONIALS.map(t => (
            <div key={t.name} className="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700">
              <div className="flex text-amber-400 mb-3">{'★'.repeat(t.stars)}</div>
              <p className="text-slate-600 dark:text-slate-400 text-sm italic mb-4">&ldquo;{t.text}&rdquo;</p>
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">👤</div>
                <div>
                  <div className="font-bold text-slate-800 dark:text-white text-xs">{t.name}</div>
                  <div className="text-slate-500 text-xs">{t.role}</div>
                </div>
              </div>
            </div>
          ))}
          {/* Feedback Card */}
          <div className="bg-emerald-700 rounded-2xl p-6 text-white flex flex-col justify-between">
            <div>
              <h3 className="font-bold text-lg mb-1">Share Your Feedback</h3>
              <p className="text-emerald-100 text-sm mb-4">Help us serve you better</p>
            </div>
            <textarea value={feedback} onChange={e => setFeedback(e.target.value)}
              className="w-full bg-emerald-800 text-white placeholder:text-emerald-300 rounded-xl p-3 text-sm resize-none border-none outline-none mb-3" rows={3} placeholder="Write your feedback..." />
            <button className="flex items-center justify-center gap-2 px-4 py-2 border-2 border-white rounded-xl font-semibold text-sm hover:bg-emerald-600 transition">
              ✍ Give Feedback
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}

export function Contact() {
  return (
    <section id="contact-us" className="py-16 bg-white dark:bg-slate-900">
      <div className="max-w-7xl mx-auto px-4 grid md:grid-cols-3 gap-8">
        <div>
          <h3 className="text-lg font-bold text-slate-800 dark:text-white mb-2">Contact Us</h3>
          <p className="text-slate-500 text-sm mb-4">We are always here to help you grow</p>
          <div className="space-y-3">
            <div className="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
              <Mail size={16} className="text-emerald-600" /> msaslivestockagroservices@gmail.com
            </div>
            <div className="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
              <Phone size={16} className="text-emerald-600" /> 08129582957
            </div>
            <div className="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-400">
              <MapPin size={16} className="text-emerald-600 mt-0.5" /> No 21 Sarkin maska street dutsin safe lowcost Katsina State, Nigeria
            </div>
          </div>
        </div>
        <div className="flex flex-col items-center justify-center text-center border border-slate-100 dark:border-slate-700 rounded-2xl p-8">
          <h3 className="text-lg font-bold text-slate-800 dark:text-white mb-2">Get In Touch</h3>
          <p className="text-slate-500 text-sm mb-6">We respond within minutes</p>
          <div className="flex gap-3 w-full">
            <a href="tel:08129582957" className="flex-1 py-2.5 rounded-xl border-2 border-emerald-700 text-emerald-700 font-bold text-sm hover:bg-emerald-50 transition text-center">📞 Call Now</a>
            <a href="https://wa.me/2348129582957" target="_blank" rel="noopener noreferrer"
              className="flex-1 py-2.5 rounded-xl bg-green-500 text-white font-bold text-sm hover:bg-green-600 transition text-center">WhatsApp Us</a>
          </div>
        </div>
        <div className="border border-slate-100 dark:border-slate-700 rounded-2xl overflow-hidden">
          <div className="bg-slate-50 dark:bg-slate-800 p-4 h-full flex flex-col items-center justify-center text-center">
            <MapPin size={32} className="text-emerald-600 mb-3" />
            <h3 className="font-bold text-slate-800 dark:text-white mb-1">Visit Our Office</h3>
            <p className="text-slate-500 text-sm">MSAS Livestock &amp; Agro Services<br/>Katsina State, Nigeria</p>
            <a href="https://maps.google.com/?q=Katsina+Nigeria" target="_blank" rel="noopener noreferrer"
              className="mt-4 text-xs text-emerald-700 font-semibold border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50">
              View on Map →
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}

export function Footer() {
  return (
    <footer className="bg-slate-900 text-slate-400 pt-12 pb-6">
      <div className="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
        <div className="col-span-2 md:col-span-1">
          <div className="flex items-center gap-2 mb-3">
            <div className="w-8 h-8 bg-emerald-700 rounded-full flex items-center justify-center text-white text-sm">🐄</div>
            <span className="font-bold text-white text-sm">MSAS</span>
          </div>
          <p className="text-xs leading-relaxed mb-3">Livestock &amp; Agro Services<br/>Smart Agriculture · Healthy Livestock · Better Future</p>
          <div className="flex gap-3">
            {['f','▶','📸','in','🐦'].map((s, i) => (
              <div key={i} className="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs hover:bg-emerald-700 cursor-pointer transition">{s}</div>
            ))}
          </div>
        </div>
        <div>
          <h4 className="text-white font-bold text-sm mb-4">Quick Links</h4>
          <ul className="space-y-2 text-xs">
            {['Home','About Us','Services','FAQ','Marketplace','Contact Us'].map(l => (
              <li key={l}><a href="#" className="hover:text-emerald-400 transition">{l}</a></li>
            ))}
          </ul>
        </div>
        <div>
          <h4 className="text-white font-bold text-sm mb-4">Services</h4>
          <ul className="space-y-2 text-xs">
            {['Livestock Management','Poultry Management','Crop Farming','Finance Tools','Training & Resources','Partner Vets'].map(l => (
              <li key={l}><a href="#" className="hover:text-emerald-400 transition">{l}</a></li>
            ))}
          </ul>
        </div>
        <div>
          <h4 className="text-white font-bold text-sm mb-4">Follow Us</h4>
          <div className="grid grid-cols-3 gap-2">
            {[['🔵','Facebook'],['🔴','YouTube'],['📷','Instagram'],['💼','LinkedIn'],['🐦','Twitter'],['📱','TikTok']].map(([ic, lb]) => (
              <a key={lb} href="#" className="flex flex-col items-center gap-1 p-2 bg-slate-800 rounded-xl hover:bg-emerald-800 transition cursor-pointer">
                <span className="text-lg">{ic}</span>
                <span className="text-[9px]">{lb}</span>
              </a>
            ))}
          </div>
        </div>
      </div>
      <div className="border-t border-slate-800 pt-6 max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-xs gap-2">
        <p>© 2024 MSAS Livestock &amp; Agro Services. All rights reserved.</p>
        <div className="flex gap-4">
          <a href="#" className="hover:text-white">Privacy Policy</a>
          <a href="#" className="hover:text-white">Terms &amp; Conditions</a>
        </div>
      </div>
    </footer>
  );
}
