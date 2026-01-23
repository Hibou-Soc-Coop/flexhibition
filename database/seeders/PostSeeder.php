<?php

namespace Database\Seeders;

use App\Services\MediaService;
use App\Models\Post;
use App\Models\Media;
use App\Models\Museum;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mediaService = app(MediaService::class);

        // Create 11 posts, each with 1 image and the same audio
        $postNames = ['Introduzione'];
        $postDescriptions = [
            'Titinu: «Oh ciao! Non vi avevo notato! Vi state guardando intorno curiosi o vi state chiedendo che strano posto è questo? In effetti anche io, la prima volta che sono arrivato ero un po’ stupito. Era la prima volta che notavo questa breve scala che scendeva verso un giardino silenzioso. Mi sembrava un posto interessante e così, subito, mi ci sono infilato. Mi è sembrato subito un posto magico, un po’ misterioso e nascosto rispetto a tutti quelli che passavano, in macchina o a piedi, sulla strada lì  vicino. Arrivato nel giardino mi sono guardato intorno: avevo ragione, quel posto non  era come tutti gli altri! Gli alberi, dalle foglie scure, mi proteggevano dal sole e  tutt’intorno correvano dei piccoli canali con dell’acqua ma non era quello a stupirmi di  più. Voltandomi a destra e a sinistra vedevo delle figure silenziose: statue  completamente bianche o nere, altre che non capivo bene cosa raffigurassero. A quel  punto un po’ di curiosità mi era venuta: in che posto ero capitato? Cosa  rappresentavano quelle statue? Alcune mi ricordavano delle persone, ma non ero  proprio sicuro. Dovevo assolutamente indagare! Così ho camminato fino alla porta,  sono entrato e ho sceso le scale. In quelle due sale c’era di tutto: quadri con mille  dettagli e bandiere piccolissime, così piccole che quasi non le vedevi, strane sculture  con delle corna e il pianeta terra tutto ricoperto di piccole figurine di bronzo! Anche i  materiali erano un po’ strani e non mi sembravano quelli che usiamo quando facciamo  le ore di arte: c’era il ferro, dei tessuti e persino delle sculture in cemento: come quello  dei muri o dei marciapiedi. Ci ho messo un po\' a mettere ordine le idee, ho dovuto chiedere a un po’ di persone e sono tornato più di una volta. Ora  potrei quasi definirmi un esperto! Ho scoperto che tutte quelle opere, tutti quegli oggetti  strani, avevano un significato profondo ma semplice, che chiunque può capire. Come  se non bastasse dietro c’erano delle mani speciali: le mani di un uomo coraggioso con  una vita davvero avventurosa. Per saperne di più dobbiamo scendere le scale.. Ah, aspettate, non vi ho raccontato nulla su di me: Orani è la mia casa e io abito  proprio qui vicino, in fondo alla strada. La mia famiglia e i miei amici mi chiamano tutti  Titinu, potete chiamarmi così anche voi. Ora entriamo, vi faccio conoscere una persona  speciale.»',];

        $postNamesEng = [
            'Introduction'
        ];

        $postDescriptionsEng = [
            'Titinu: "Oh hello! I hadn’t noticed you! Are you looking around out of curiosity or are you wondering what kind of unusual place this is? Actually, even I was a bit surprised the first time I came here. It was the first time I noticed this short staircase leading to a quiet garden. It seemed to me like an interesting place, so I slipped inside right away. It immediately struck me as a magical place, a bit mysterious and hidden from all passers-by, by car or on foot, along the road running beside it. Once inside the garden, I looked around: I was right, this place is unlike any other! The trees, with their dark leaves, protected me from the sun and all around ran small channels of water, but that was not what surprised me the most. As I turned to the right and the left, I saw silent figures: entirely white or black statues, and others whose forms I could not distinguish. By then, my curiosity was piqued: what kind of place had I wandered into? What did these statues represent? Some of them remind me of people, but I wasn’t entirely sure. I absolutely had to find out! So, I walked to the door, went inside and went down the stairs. Those two rooms contained everything: paintings with thousands of details and tiny flags, some so small I could hardly see them, strange sculptures with horns and planet earth entirely covered with minute bronze figures! Even the materials were somewhat unusual and they did not look to me like the materials we normally use in art class: there was iron, textiles and even concrete sculptures: like those used in walls or pavements. It took me a while to order my thoughts; I had to ask quite a few people and I came back more than once. By now, was almost an expert! I realised that all these works, all these strange objects, had a profound but simple meaning, that anyone could understand. As if that were not enough, there were some special hands behind it: the hands of an courageous man who led an adventurous life. To find out more, we must down the stairs.. Oh, wait, I haven’t told you anything about myself yet: Orani is my home and I live close by, at the end of the street. My family and friends all call me Titinu, and you can also call me that. Once we go in, I will introduce you to a special person."',
            'Titinu: "Here I am again! Are you wondering what these figures are? How are they meant to be viewed and from what strange material are they made? I understand… even I had the same questions at the beginning. For now, all I can tell you is that they were created by the person with the special hands, I already told you about, whose name was Costantino Nivola. But to understand something more about him, the person we have to speak to is Ruth: she knows everything." Ruth: "Oh, there you are, Titinu, and I see several new faces behind you! How wonderful to be here with you and to be able to tell you about the adventures I have experienced and about Costantino, Antine as I used to call him, who was with me the whole time. My name is Ruth Guggenheim. What? What did you say? It seems to be an unusual surname? Well, you are right! My family was of Jewish origin and I was born in Germany in a year which now seems very long ago: 1917. Those were very difficult years: even though I was only a child, I well remember how worried my father was because in Germany crimes and violence against Jews were increasing. So, we fled to Italy, without knowing that that troubles would catch up with us even there … but I’ll tell you more about that later on. By this time, I was no longer a child but girl of 16. Even after changing country and city, my passions remained intact: I was interested in art and loved to create. So, I enrolled in the Art Institute and it was there that I met Antine. I must say, it wasn’t hard to notice him: in the entire school, which was in Monza, there were only three lads from Sardinia, each more talented than the last. Antine and I were drawn to each other immediately, and we were never parted after that, even though we came from very different worlds, he from a tiny village in Sardinia and I from a large German city. Every work in this museum reminds me of a different time in our life together. Here, for example, Antine had just landed an important job. Try to imagine being on Fifth Avenue in New York, one of the most famous streets in the world. Cars, yellow taxis, people walking fast, towering skyscrapers. In the middle of all this, in the fifties, there was a special shop owned by Olivetti, who sold typewriters and calculators, but who also a new idea about beauty and work. Adriano Olivetti, the director, believed that when people entered the workplace, they deserved to find harmony, light and beauty. This is the reason he called in modern architects and artists, among whom was Antine himself. The sketches which you see here are his first thoughts for decorating the walls and façades of the shop. It was a revolutionary idea: one didn’t have to go to a museum to see art but it was there, mixed in with everyday items. But let’s move on – I want to show you another work …"',
            'Ruth: "Now I’m going to take you to a very different place from the streets of New York and shops full of people. A small room so rich in colours and filled with objects that it’s difficult to make them all out. I am very attached to this painting because on the right you can see me, Ruth and a little higher up, almost as though suspended in mid-air, is Pietro, our first child. As you can see, I am no longer a young girl but a woman. If you look a bit closer you will see I am wearing a concentrated and rather serious expression. No wonder! By this stage Antine and I had already faced thousands of difficulties! A few years after we met, racial laws were introduced even in Italy and life for Jews became even more difficult. For this reason, and so we could stay together, Antine and I decided to escape together to America. It seemed like a real adventure, but in actual fact it was very difficult: Antine did not speak English well and he struggled to find work. I, on the other hand, knew English a bit better and started working in a factory and then as a nanny. Little by little things improved: Antine found a job that he enjoyed very much, as a graphic designer for American magazines and got to know some of the great artists and architects of the time, including Corbusier, who became a great friend. The painting you see before you dates back to this period: our home was his refuge. In this picture you can see me preparing a meal, surrounded by plates, fruit and bottles. It may seem like an everyday scene, such as you would see in your own home on any day. However, for Antine, this was a very important subject: everyday life, care for others. If you look at the painting more closely, you will see many details: our dinner, the lamp which hung from the ceiling but also the brushes and the palette that Antine used every day. The colours also remind me of a warm blanket and make me feel at home. Titinu will tell you about the next work, since by now he knows it very well and it is one of his favourites."'];

        $postNamesFr = [
            'Introduction',
            'Ebauche pour la décoration murale du showroom Olivetti à New York, 1953',
            'Ruth et Pietro dans la cuisine (Nature morte avec Ruth Guggenheim), 1946',
            'Figure de femme, d’après “Gli antenati”, 1952',
            'Ebauche pour la façade Hartford, Connecticut, 1957-1958',
            'Figure d’homme, Pastore Sardo, 1966',
            'Pergola Village, projet pour Orani, 1953',
            'Trois tableaux de la série Ritratti di sculture/Portraits de sculptures, 1975',
            'Modèles pour le monument au drapeau américain, années 1980',
            'La città incredibile/La ville incroyable, 1979',
            'Madre/Mère, d’après modèle, 1981-1985',
            'La terra sovrappopolata/La terre surpeuplée, 1972'
        ];

        $postDescriptionsFr = [
            'Titinu: «Salut! Je ne vous avez pas vus! Vous jetez autour de vous un coup d’oeil plein de curiosité ou vous vous demandez quel est cet endroit étrange? Je dois avouer que la première fois que je suis venu ici, moi aussi j’étais un peu étonné. Je n’avais jamais remarqué ce petit escalier qui descend vers ce jardin silencieux. Cet endroit m’a semblé intéressant et j’ai eu tout de suite envie de le visiter. J’ai eu  l’impression que c’était un lieu magique, un peu mystérieux, à l’abri des passants ou des gens qui le longeaient en voiture. Une fois arrivé dans le jardin, je me suis dit que j’avais eu raison : ce n’était pas un endroit comme les autres! Les arbres, avec leurs feuilles sombres, me protégeaient du soleil et il y avait, tout autour, de petits canaux remplis d’eau, mais ce n’était pas cela le plus étonnant. A ma droite et à ma gauche, il y avait des figures silencieuses, des statues entièrement blanches ou noires et d’autres dont je ne comprenais pas exactement ce qu’elles représentaient. Là, j’ai vraiment commencé à me demander : où suis-je? Que représentent ces sculptures? Certaines d’entre elles auraient pu représenter des figures humaines, mais je n’en étais pas entièrement sûr. Il fallait absolument que je mène mon enquête! J’ai donc marché jusqu’à la porte, je suis entré et j’ai emprunté l’escalier. Ces deux salles contenaient toutes sortes de choses : des tableaux avec une infinité de détails et de tout petits drapeaux, presqu’invisibles, d’étranges sculptures avec des cornes et le globe terrestre recouvert de figures en bronze! Les matériaux aussi étaient assez étranges : ils ne ressemblaient pas à ceux que nous utilisons à l’école, pendant les cours d’arts plastiques. Il y avait du fer, des tissus et même des sculptures en béton, le béton  que l’on utilise pour construire des murs ou des trottoirs. Il m’a fallu un peu de temps pour comprendre, j’ai du poser des questions à des gens  et je suis retourné là-bas plusieurs fois. Maintenant, je suis presque devenu un expert! J’ai découvert que toutes ces œuvres, tous ces objets étranges avaient une signification à la fois profonde et simple, que tout le monde pouvait  comprendre. Et derrière tout cela, il y avait le travail, tout à fait spécial, d’un homme courageux, qui avait eu une vie aventureuse. Pour en savoir plus, il faut descendre ces marches... Mais, attendez une minute, je ne vous ai rien dit de moi : je vis à Orani, j’habite au bout de la  rue. Tout le monde m’appelle Titinu : vous aussi, vous pouvez m’appeler comme ça. Entrons: je vais vous présenter une personne vraiment spéciale.»'];

        $postImages = [];

        for ($i = 0; $i <= count($postNames) - 1; $i++) {


            $imageSourcePath = resource_path("assets/collections/$i.png");

            // Create temp files for each language to avoid file locking/moving issues
            $imageTempPathIt = tempnam(sys_get_temp_dir(), 'it');
            copy($imageSourcePath, $imageTempPathIt);
            $imageFileIt = new UploadedFile($imageTempPathIt, "$i.png", 'image/png', null, true);

            $imageTempPathEn = tempnam(sys_get_temp_dir(), 'en');
            copy($imageSourcePath, $imageTempPathEn);
            $imageFileEn = new UploadedFile($imageTempPathEn, "$i.png", 'image/png', null, true);
            $imageMedia = $mediaService->createMedia(
                'image',
                ['it' => $imageFileIt, 'en' => $imageFileEn],
                ['it' => 'Opera_' . $i, 'en' => 'Artwork_' . $i],
                ['it' => "Immagine ufficiale dell'opera $i", 'en' => "Official image of the artwork $i"]
            );

            $audioSourcePath = resource_path("assets/collections/$i.mp3");

            // Create temp files for each language to avoid file locking/moving issues
            $audioTempPathIt = tempnam(sys_get_temp_dir(), 'it');
            copy(resource_path("assets/collections/$i.mp3"), $audioTempPathIt);
            $audioFileIt = new UploadedFile($audioTempPathIt, "$i.mp3", 'audio/mpeg', null, true);

            $audioTempPathEn = tempnam(sys_get_temp_dir(), 'en');
            copy(resource_path("assets/collections/$i-en.mp3"), $audioTempPathEn);
            $audioFileEn = new UploadedFile($audioTempPathEn, "$i.mp3", 'audio/mpeg', null, true);

            $audioTempPathFr = tempnam(sys_get_temp_dir(), 'fr');
            copy(resource_path("assets/collections/$i-fr.mp3"), $audioTempPathFr);
            $audioFileFr = new UploadedFile($audioTempPathFr, "$i.mp3", 'audio/mpeg', null, true);
            $audioMedia = $mediaService->createMedia(
                'audio',
                ['it' => $audioFileIt, 'en' => $audioFileEn, 'fr' => $audioFileFr],
                ['it' => 'Opera_' . $i, 'en' => 'Artwork_' . $i, 'fr' => 'Oeuvre_' . $i],
                ['it' => "Immagine ufficiale dell'opera $i", 'en' => "Official audio of the artwork $i", 'fr' => "Audio officiel de l'oeuvre $i"]
            );

            $post = Post::create([
                'name' => [
                    'it' => $postNames[$i],
                    'en' => $postNamesEng[$i],
                    'fr' => $postNamesFr[$i],
                ],
                'description' => [
                    'it' => $postDescriptions[$i],
                    'en' => $postDescriptionsEng[$i],
                    'fr' => $postDescriptionsFr[$i],
                ],
                'content' => [
                    'it' => "Contenuto dettagliato del post della collezione {$i}",
                    'en' => "Detailed content of the collection post {$i}",
                ],
                'audio_id' => $audioMedia->id,
                'exhibition_id' => 1, // Adjust as needed
            ]);

            // Attach the image to the post
            $post->images()->attach([$imageMedia->id]);
        }
    }
}
