#include "inkview.h"
#include <math.h>
#include <unistd.h>
#include <pthread.h>
#include <algorithm>
#include <libxml/tree.h>
#include <libxml/parser.h>
#include <libxml/xpath.h>
#include <libxml/xpathInternals.h>

static const int kFontSize = 42;

static char* get_data(char* calendar_name)
{
	char buffer[2048];

	char url[2048];
    strcpy(url, "http://192.168.1.90:1234/web/?calendar=");
    strcat(url, calendar_name);

    int retsize;
    char *cookie = NULL;
    char *post = NULL;

    void *result = QuickDownloadExt(url, &retsize, 15, cookie, post);

    char *content = strncpy(buffer, (char *)result, sizeof(buffer) - 1);

    return content;
}

static void update_screen(void* calendar_name)
{
    ifont *font = OpenFont("LiberationSans", kFontSize, 0);

    char *data = get_data((char*)calendar_name);
    xmlDocPtr doc = xmlParseDoc(BAD_CAST data);
    xmlXPathContextPtr xpathCtx = xmlXPathNewContext(doc);

    SetFont(font, BLACK);
    ClearScreen();

    // Current
    FillArea(30, 30, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15, 0x00A0A0A0);
    xmlXPathObjectPtr xpathObj = xmlXPathEvalExpression(BAD_CAST"/meetings/current", xpathCtx);
    xmlNodeSetPtr nodeSet = xpathObj->nodesetval;
    xmlNodePtr node = nodeSet->nodeTab[0];
    DrawTextRect(
        30, 30, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
        "MAINTENANT",
        ALIGN_CENTER
    );
    if (node->xmlChildrenNode == NULL) {
        DrawTextRect(
            30, ScreenHeight() / 4, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            "La salle est libre",
            ALIGN_CENTER
        );
    } else {
        xmlNodePtr summary = xmlFirstElementChild(node);
        xmlNodePtr creator = summary->next;
        xmlNodePtr date_start = creator->next;
        xmlNodePtr date_end = date_start->next;
        DrawTextRect(
            30, 30 + 100, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            (char *) xmlNodeGetContent(summary),
            ALIGN_CENTER
        );
        DrawTextRect(
            30, 30 + 250, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            (char *) xmlNodeGetContent(creator),
            ALIGN_CENTER
        );

        char label[30];
        strcpy(label, "Jusqu'à ");
        strcat(label, (char *) xmlNodeGetContent(date_end));
        DrawTextRect(
            30, 30 + 310, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            label,
            ALIGN_CENTER
        );
    }

    // Next
    FillArea(30, ScreenHeight() / 2 + 30, ScreenWidth() - 30*2, ScreenHeight() / 2 - 2*30, 0x00E0E0E0);
    xpathObj = xmlXPathEvalExpression(BAD_CAST"/meetings/next", xpathCtx);
    nodeSet = xpathObj->nodesetval;
    node = nodeSet->nodeTab[0];
    DrawTextRect(
        30, ScreenHeight() / 2 + 30, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
        "APRES",
        ALIGN_CENTER
    );
    if (node->xmlChildrenNode == NULL) {
        DrawTextRect(
            30, ScreenHeight() / 2 + ScreenHeight() / 4, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            "Rien de prévu aujourd'hui !",
            ALIGN_CENTER
        );
    } else {
        xmlNodePtr summary = xmlFirstElementChild(node);
        xmlNodePtr creator = summary->next;
        xmlNodePtr date_start = creator->next;
        xmlNodePtr date_end = date_start->next;
        DrawTextRect(
            30, ScreenHeight() / 2 + 30 + 100, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            (char *) xmlNodeGetContent(summary),
            ALIGN_CENTER
        );
        DrawTextRect(
            30, ScreenHeight() / 2 + 30 + 250, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            (char *) xmlNodeGetContent(creator),
            ALIGN_CENTER
        );
        char label[30];
        strcpy(label, "A partir de ");
        strcat(label, (char *) xmlNodeGetContent(date_start));
        DrawTextRect(
            30, ScreenHeight() / 2 + 30 + 310, ScreenWidth() - 30*2, ScreenHeight() / 2 - 15,
            label,
            ALIGN_CENTER
        );
    }

    FullUpdate();

    CloseFont(font);
}

static void *update_screen_async(void *arg)
{
    sleep(900);
    update_screen(arg);
}

static void launch(char* calendar_name)
{
    update_screen(calendar_name);
    pthread_t update_thread;
    while (true) {
        pthread_create(&update_thread, NULL, update_screen_async, calendar_name);
        pthread_join(update_thread, NULL);
    }
}

static void display_message(char* message)
{
    ClearScreen();
    DrawTextRect(
        30, ScreenHeight() / 2, ScreenWidth() - 30, ScreenHeight() / 2 +30,
        message,
        ALIGN_CENTER
    );
    FullUpdate();
}

static int main_handler(int event_type, int param_one, int param_two)
{
	static int step = 0;

    ifont *font = OpenFont("LiberationSans", kFontSize, 0);
    SetFont(font, BLACK);
	switch (event_type) {
	case EVT_INIT:
            display_message("Utilisez la flèche de droite pour sélectionner la salle puis appuyez sur la touche menu pour lancer le rafraichissement automatique.");
		break;
	case EVT_SHOW:

		break;
	case EVT_KEYPRESS:
		if (param_one == KEY_NEXT) {
			if (step == 0) {
                ++step;
                display_message("bureau_passage");
			}
			else if (step == 1) {
                ++step;
				display_message("bureau_nico");
			}
			else if (step == 2) {
                step = 0;
				display_message("salle_reunion");
			}
			return 1;
		}
		else if (param_one == KEY_MENU) {
			if (step == 0) {
                launch("salle_reunion");
			}
			else if (step == 1) {
				launch("bureau_passage");
			}
			else if (step == 2) {
				launch("bureau_nico");
			}
			return 1;
		}
        else {
            CloseApp();
        }

		break;
	case EVT_EXIT:

		break;
	default:
		break;
	}
    CloseFont(font);

    return 0;
}


int main (int argc, char* argv[])
{
    InkViewMain(main_handler);
    return 0;
}
