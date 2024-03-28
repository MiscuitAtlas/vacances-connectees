const images = [
    "image1.jpg",
    "image2.jpg",
    "image3.jpg"
];

let currentImageIndex = 0;
const imageContainer = document.getElementById("image-container");
const nextButton = document.getElementById("next-button");
const prevButton = document.getElementById("prev-button");

function showNextImage() {
    currentImageIndex = (currentImageIndex + 1) % images.length;
    updateImage();
}

function showPreviousImage() {
    currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
    updateImage();
}

function updateImage() {
    const image = imageContainer;
    image.style.opacity = 0;

    setTimeout(function() {
        image.src = images[currentImageIndex];
        setTimeout(function() {
            image.style.opacity = 1;
        }, 100);
    }, 500);
}

nextButton.addEventListener("click", showNextImage);
prevButton.addEventListener("click", showPreviousImage);

updateImage();

const imagesWithCaptions = [
    { src: "image1.jpg", caption: "Logement 1 - Appartement moderne près de la plage : Un appartement entièrement équipé avec vue sur la mer, situé à quelques pas de la plage. Comprend deux chambres, une cuisine moderne et un balcon offrant une vue panoramique sur l'océan." },
    { src: "image2.jpg", caption: "Logement 2 - Chalet rustique en montagne : Un charmant chalet en bois situé au cœur des montagnes. Avec trois chambres confortables, une cheminée, et un jacuzzi extérieur offrant une vue imprenable sur les sommets enneigés." },
    { src: "image3.jpg", caption: "Logement 3 - Villa luxueuse avec piscine privée : Une villa moderne et spacieuse comprenant cinq chambres, un jardin luxuriant, et une piscine privée. Idéale pour des vacances en famille ou entre amis, à proximité des attractions locales et offrant un confort absolu." }
];

let currentImageIndexWithCaption = 0;
const imageContainerWithCaption = document.getElementById("image-container");
const captionContainer = document.getElementById("caption-container");
const nextButtonWithCaption = document.getElementById("next-button");
const prevButtonWithCaption = document.getElementById("prev-button");

function showNextImageWithCaption() {
    currentImageIndexWithCaption = (currentImageIndexWithCaption + 1) % imagesWithCaptions.length;
    updateImageWithCaption();
}

function showPreviousImageWithCaption() {
    currentImageIndexWithCaption = (currentImageIndexWithCaption - 1 + imagesWithCaptions.length) % imagesWithCaptions.length;
    updateImageWithCaption();
}

function updateImageWithCaption() {
    imageContainerWithCaption.src = imagesWithCaptions[currentImageIndexWithCaption].src;
    captionContainer.textContent = imagesWithCaptions[currentImageIndexWithCaption].caption;
}

nextButtonWithCaption.addEventListener("click", showNextImageWithCaption);
prevButtonWithCaption.addEventListener("click", showPreviousImageWithCaption);

updateImageWithCaption();

const logements = [
    {
      image: 'image1.jpg',
      avis: [
        { texte: "Notre séjour dans le logement était incroyable. L'emplacement était parfait et les commodités étaient exactement ce que nous cherchions.", auteur: "Alice" },
        { texte: "Je recommande fortement ce logement à quiconque cherche un endroit confortable et abordable pour des vacances relaxantes.", auteur: "Bob" },
        { texte: "Les hôtes étaient également très accueillants. Nous le recommandons vivement à tous ceux qui visitent la région.", auteur: "Charlie" }
      ]
    },
    {
      image: 'image2.jpg',
      avis: [
        { texte: "Le chalet était parfait pour notre escapade hivernale. Le foyer était chaleureux, et la proximité des pistes de ski était un vrai plus.", auteur: "David" },
        { texte: "L'endroit était propre et bien entretenu. Nous avons passé un excellent séjour en famille.", auteur: "Eva" },
        { texte: "L'emplacement était central, ce qui a facilité nos déplacements. Les propriétaires étaient très accueillants et serviables.", auteur: "Frank" }
      ]
    },
    {
      image: 'image3.jpg',
      avis: [
        { texte: "Nous avons adoré notre séjour dans cette villa. La vue était magnifique et les équipements étaient de première classe.", auteur: "Violette" },
        { texte: "Excellent emplacement et service. Je reviendrai certainement ici lors de mes prochaines vacances.", auteur: "Henry" },
        { texte: "Cet appartement moderne a dépassé nos attentes. L'emplacement central était idéal pour explorer la ville, et la décoration intérieure était élégante.", auteur: "Ivy" }
      ]
    }
  ];
  
    let currentLogementIndex = 0;
    let currentAvisIndex = 0;
    
    document.addEventListener('DOMContentLoaded', function () {
        changeLogement(currentLogementIndex);
    });

    function changeAvis() {
        const avisActuels = logements[currentLogementIndex].avis;

        for (let i = 1; i <= avisActuels.length; i++) {
            document.getElementById(`avi${i}`).style.display = 'none';
        }

        currentAvisIndex++;

        if (currentAvisIndex >= avisActuels.length) {
            currentAvisIndex = 0;
        }

        document.getElementById(`avi${currentAvisIndex + 1}`).style.display = 'block';
    }

    function changeLogement(logementIndex) {
        const nouvelAvis = logements[logementIndex].avis;
        for (let i = 0; i < nouvelAvis.length; i++) {
            const avisElement = document.getElementById(`avi${i + 1}`);
            avisElement.innerHTML = `<blockquote>${nouvelAvis[i].texte}</blockquote><p class="author">- ${nouvelAvis[i].auteur}</p>`;
            avisElement.style.display = 'block';
        }
    
        document.getElementById('image-container').src = logements[logementIndex].image;
    }

    document.getElementById('next-button').addEventListener('click', function () {
        changeAvis();
        currentLogementIndex++;
        if (currentLogementIndex >= logements.length) {
            currentLogementIndex = 0;
        }
        changeLogement(currentLogementIndex);
    });

    document.getElementById('prev-button').addEventListener('click', function () {
        changeAvis();
        currentLogementIndex--;
        if (currentLogementIndex < 0) {
            currentLogementIndex = logements.length - 1;
        }
        changeLogement(currentLogementIndex);
    });