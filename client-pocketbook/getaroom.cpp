#include "inkview.h"
#include "curl/curl.h"
#include <math.h>
#include <algorithm>

static const int kFontSize = 42;

static char* get_data()
{
    char buffer[2048];

    const char *url = "http://192.168.10.2:8001/?calendar=salle_reunion";
    int retsize;
    char *cookie = NULL;
    char *post = NULL;

    void *result = QuickDownloadExt(url, &retsize, 15, cookie, post);

    char *content = strncpy(buffer, (char *)result, sizeof(buffer) - 1);

    return content;
}

static int main_handler(int event_type, int param_one, int param_two)
{
    if (EVT_INIT == event_type) {
        ifont *font = OpenFont("LiberationSans", kFontSize, 0);

        char *data = get_data();

        // Effacement de l'écran
        ClearScreen();

        // Affichages sur un buffer
        SetFont(font, BLACK);
        DrawLine(0, 25, ScreenWidth(), 25, 0x00333333);
        DrawLine(0, ScreenHeight() - 25, ScreenWidth(), ScreenHeight() - 25, 0x00666666);
        FillArea(50, 250, ScreenWidth() - 50*2, ScreenHeight() - 250*2, 0x00E0E0E0);
        FillArea(100, 300, ScreenWidth() - 100*2, ScreenHeight() - 300*2, 0x00A0A0A0);
        DrawTextRect(0, ScreenHeight()/2 - kFontSize/2, ScreenWidth(), kFontSize, data, ALIGN_CENTER);

        // Copie du buffer vers l'écran
        FullUpdate();

        CloseFont(font);
    }
    else if (EVT_KEYPRESS == event_type) {
        CloseApp();
    }
    return 0;
}


int main (int argc, char* argv[])
{
    InkViewMain(main_handler);
    return 0;
}
