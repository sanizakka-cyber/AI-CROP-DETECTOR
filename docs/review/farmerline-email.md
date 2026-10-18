# Draft email to Farmerline (for Sani to send)

Send it through Farmerline's own contact page, or to a named person you know there. I have not guessed an address.

---

**Subject:** Licence question about FarmerlineML/main_hausa_TTS

Dear Farmerline team,

I am Sani Yawale Zakka, CEO of MSAS Livestock & Agro Services in Katsina State, Nigeria. We run MSAS FarmAI, an app that gives smallholder farmers AI guidance on crop, livestock, soil and pest problems. We would like to read results aloud to farmers in Hausa. Your Hausa text-to-speech model on Hugging Face, FarmerlineML/main_hausa_TTS, is a strong candidate.

Before we use it, we need to be sure about its licence. The model card shows CC-BY-4.0 but does not say how the model was trained. Its size and architecture match Meta's MMS Hausa model, facebook/mms-tts-hau, which is licensed CC-BY-NC-4.0 and does not allow commercial use.

Could you please confirm in writing:

1. Is main_hausa_TTS fine-tuned from facebook/mms-tts-hau, or from any other model? If so, which one, and under what licence?
2. If it is not derived from MMS, was it trained from scratch, and on what data? Does any of that data restrict commercial use?
3. May MSAS use the model commercially, inside a paid farmer app, under CC-BY-4.0?
4. What attribution wording would you like us to show? We would show it on the app's Credits screen.

We will keep the model switched off until we hear from you. We would be glad to share feedback from our native-speaker reviewers on its Hausa quality.

Thank you,

Sani Yawale Zakka
CEO, MSAS Livestock & Agro Services
Katsina, Nigeria
[phone]

---

**What changes depending on the answer:**
- **CC-BY-4.0 confirmed, and not derived from MMS:** set `SPEECH_FARMERLINE_CONFIRMED=true`. Its Hausa voice then joins the next review pack as a candidate. Keep their written reply on file.
- **Derived from MMS (CC-BY-NC):** it cannot be used in MSAS FarmAI. Hausa stays on OpenBible.
